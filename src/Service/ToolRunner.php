<?php

declare(strict_types=1);

namespace Linters\Service;

use Linters\ConfigGenerator\ComposerUnusedConfigGenerator;
use Linters\ConfigGenerator\ConfigGeneratorInterface;
use Linters\ConfigGenerator\PhpCsConfigGenerator;
use Linters\ConfigGenerator\PhpCsFixerConfigGenerator;
use Linters\ConfigGenerator\PhpMdConfigGenerator;
use Linters\ConfigGenerator\PhpStanConfigGenerator;
use Linters\ConfigGenerator\RectorConfigGenerator;
use Linters\Enum\Tool;
use Linters\Utils\ConfigurationLoader;
use Linters\Utils\ConfigValidation;
use RuntimeException;
use Safe\Exceptions\DirException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;

final readonly class ToolRunner
{
    private const string DEFAULT_PHP_MD_FORMAT = 'text';

    public function __construct(private ConfigurationLoader $loader)
    {
    }

    public function generate(Tool $tool): string
    {
        $target = $this->resolveGeneratedTarget($tool);
        $generator = $this->createGenerator($tool);
        $generator->generate($target);

        return $target;
    }

    /**
     * @param string[] $extraArgs
     *
     * @throws DirException
     */
    public function run(Tool $tool, OutputInterface $output, array $extraArgs = []): int
    {
        $target = '';

        if ($tool->requiresGeneration()) {
            $output->writeln('Generating: ' . $tool->label() . ' config');
            $target = $this->generate($tool);
        }

        $command = $this->buildCommand($tool, $target, $extraArgs);

        return $this->runCommand($output, $tool->label(), $command);
    }

    private function createGenerator(Tool $tool): ConfigGeneratorInterface
    {
        return match ($tool) {
            Tool::PHP_STAN => new PhpStanConfigGenerator($this->loader),
            Tool::PHP_CS, Tool::PHP_CBF => new PhpCsConfigGenerator($this->loader),
            Tool::PHP_MD             => new PhpMdConfigGenerator($this->loader),
            Tool::RECTOR             => new RectorConfigGenerator($this->loader),
            Tool::PHP_CS_FIXER       => new PhpCsFixerConfigGenerator($this->loader),
            Tool::COMPOSER_UNUSED    => new ComposerUnusedConfigGenerator($this->loader),
            Tool::COMPOSER_NORMALIZE => throw new RuntimeException('No generator for this tool'),
        };
    }

    /**
     * @param string[] $extraArgs
     */
    private function buildCommand(Tool $tool, string $target, array $extraArgs): string
    {
        $bin = $this->resolveBinary($tool->value);

        return match ($tool) {
            Tool::PHP_STAN           => $this->buildPhpStanCommand($bin, $target, $extraArgs),
            Tool::PHP_CS             => $this->buildPhpCsCommand($bin, $target, $extraArgs),
            Tool::PHP_CBF            => $this->buildPhpCbfCommand($bin, $target, $extraArgs),
            Tool::PHP_MD             => $this->buildPhpMdCommand($bin, $target, $extraArgs),
            Tool::RECTOR             => $this->buildRectorCommand($bin, $target, $extraArgs),
            Tool::PHP_CS_FIXER       => $this->buildPhpCsFixerCommand($bin, $target, $extraArgs),
            Tool::COMPOSER_UNUSED    => $this->buildComposerUnusedCommand($bin, $target, $extraArgs),
            Tool::COMPOSER_NORMALIZE => $this->buildComposerNormalizeCommand($extraArgs),
        };
    }

    /**
     * @param string[] $extraArgs
     */
    private function buildPhpStanCommand(string $binary, string $target, array $extraArgs): string
    {
        $command = escapeshellarg($binary) . ' analyze --configuration=' . escapeshellarg($target);

        return $command . $this->buildExtraArgs($extraArgs);
    }

    /**
     * @param string[] $extraArgs
     */
    private function buildPhpCsCommand(string $binary, string $target, array $extraArgs): string
    {
        $command = escapeshellarg($binary) . ' --standard=' . escapeshellarg($target);

        $config = $this->loader->getPhpCsConfig();
        $parallel = $config->parallel;

        if ($parallel?->enabled === true && $parallel->maxProcesses !== null) {
            $command .= ' --parallel=' . $parallel->maxProcesses;
        }

        return $command . $this->buildExtraArgs($extraArgs);
    }

    /**
     * @param string[] $extraArgs
     */
    private function buildPhpCbfCommand(string $binary, string $target, array $extraArgs): string
    {
        $command = escapeshellarg($binary) . ' --standard=' . escapeshellarg($target);

        $config = $this->loader->getPhpCsConfig();
        $parallel = $config->parallel;

        if ($parallel?->enabled === true && $parallel->maxProcesses !== null) {
            $command .= ' --parallel=' . $parallel->maxProcesses;
        }

        return $command . $this->buildExtraArgs($extraArgs);
    }

    /**
     * @param string[] $extraArgs
     */
    private function buildPhpMdCommand(string $binary, string $target, array $extraArgs): string
    {
        $config = $this->loader->getPhpMdConfig();
        $paths = $config->paths;

        $format = self::DEFAULT_PHP_MD_FORMAT;

        $command = escapeshellarg($binary)
            . ' ' . escapeshellarg(implode(',', $paths))
            . ' ' . escapeshellarg($format)
            . ' ' . escapeshellarg($target);

        $baseline = $config->baseline;

        if (ConfigValidation::isNonEmptyString($baseline)) {
            $command .= ' --baseline-file=' . escapeshellarg((string) $baseline);
        }

        return $command . $this->buildExtraArgs($extraArgs);
    }

    /**
     * @param string[] $extraArgs
     */
    private function buildRectorCommand(string $binary, string $target, array $extraArgs): string
    {
        $command = escapeshellarg($binary)
            . ' process --config=' . escapeshellarg($target)
            . ' --clear-cache';

        return $command . $this->buildExtraArgs($extraArgs);
    }

    /**
     * @param string[] $extraArgs
     */
    private function buildPhpCsFixerCommand(string $binary, string $target, array $extraArgs): string
    {
        $command = escapeshellarg($binary)
            . ' fix --config=' . escapeshellarg($target)
            . ' --allow-risky=yes';

        return $command . $this->buildExtraArgs($extraArgs);
    }

    /**
     * @param string[] $extraArgs
     */
    private function buildComposerUnusedCommand(string $binary, string $target, array $extraArgs): string
    {
        $command = escapeshellarg($binary)
            . ' --configuration=' . escapeshellarg($target);

        return $command . $this->buildExtraArgs($extraArgs);
    }

    /**
     * @param string[] $extraArgs
     */
    private function buildComposerNormalizeCommand(array $extraArgs): string
    {
        return 'composer normalize' . $this->buildExtraArgs($extraArgs);
    }

    /**
     * @param string[] $args
     */
    private function buildExtraArgs(array $args): string
    {
        if ($args === []) {
            return '';
        }

        $escaped = array_map(escapeshellarg(...), $args);

        return ' ' . implode(' ', $escaped);
    }

    private function resolveGeneratedTarget(Tool $tool): string
    {
        $target = $tool->generatedTarget();

        if ($target === null) {
            throw new RuntimeException(\sprintf('Tool %s does not have a generated target', $tool->value));
        }

        return Path::join($this->loader->getComposerDir(), $target);
    }

    private function resolveBinary(string $binary): string
    {
        return Path::join($this->loader->getComposerDir(), 'vendor', 'bin', $binary);
    }

    private function runCommand(OutputInterface $output, string $label, string $command): int
    {
        $output->writeln('Running: ' . $label);
        passthru($command, $exitCode);

        if ($exitCode !== 0) {
            $output->writeln(\sprintf('<error>%s failed with exit code %s</error>', $label, $exitCode));
        }

        return $exitCode;
    }
}
