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
use RuntimeException;
use Safe\Exceptions\DirException;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class ToolRunner
{
    private const string DEFAULT_PHP_MD_FORMAT = 'text';

    public function __construct(private ConfigurationLoader $loader)
    {
    }

    /**
     * @throws DirException
     */
    public function generate(Tool $tool): string
    {
        $target = $this->resolveGeneratedTarget($tool);
        $generator = $this->createGenerator($tool);
        $generator->generate($target);

        return $target;
    }

    /**
     * @throws DirException
     */
    public function run(Tool $tool, OutputInterface $output): int
    {
        $target = '';

        if ($tool->requiresGeneration()) {
            $output->writeln('Generating: ' . $tool->label() . ' config');
            $target = $this->generate($tool);
        }

        $command = $this->buildCommand($tool, $target);

        return $this->runCommand($output, $tool->label(), $command);
    }

    private function createGenerator(Tool $tool): ConfigGeneratorInterface
    {
        return match ($tool) {
            Tool::PHP_STAN           => new PhpStanConfigGenerator($this->loader),
            Tool::PHP_CS             => new PhpCsConfigGenerator($this->loader),
            Tool::PHP_MD             => new PhpMdConfigGenerator($this->loader),
            Tool::RECTOR             => new RectorConfigGenerator($this->loader),
            Tool::PHP_CS_FIXER       => new PhpCsFixerConfigGenerator($this->loader),
            Tool::COMPOSER_UNUSED    => new ComposerUnusedConfigGenerator($this->loader),
            Tool::COMPOSER_NORMALIZE => throw new RuntimeException('No generator for this tool'),
        };
    }

    /**
     * @throws DirException
     */
    private function buildCommand(Tool $tool, string $target): string
    {
        if ($tool === Tool::COMPOSER_NORMALIZE) {
            return $this->buildComposerNormalizeCommand();
        }

        $bin = $this->resolveBinary($tool->binary());

        /** @var Tool::PHP_STAN|Tool::PHP_CS|Tool::PHP_MD|Tool::RECTOR|Tool::PHP_CS_FIXER|Tool::COMPOSER_UNUSED $tool */
        return match ($tool) {
            Tool::PHP_STAN        => $this->buildPhpStanCommand($bin, $target),
            Tool::PHP_CS          => $this->buildPhpCsCommand($bin, $target),
            Tool::PHP_MD          => $this->buildPhpMdCommand($bin, $target),
            Tool::RECTOR          => $this->buildRectorCommand($bin, $target),
            Tool::PHP_CS_FIXER    => $this->buildPhpCsFixerCommand($bin, $target),
            Tool::COMPOSER_UNUSED => $this->buildComposerUnusedCommand($bin, $target),
        };
    }

    private function buildPhpStanCommand(string $binary, string $target): string
    {
        return escapeshellarg($binary) . ' analyze --configuration=' . escapeshellarg($target);
    }

    private function buildPhpCsCommand(string $binary, string $target): string
    {
        $command = escapeshellarg($binary) . ' --standard=' . escapeshellarg($target);

        $config = $this->loader->getPhpCsConfig();
        $parallel = $config->parallel;

        if ($parallel?->enabled === true && $parallel->maxProcesses !== null) {
            $command .= ' --parallel=' . $parallel->maxProcesses;
        }

        return $command;
    }

    private function buildPhpMdCommand(string $binary, string $target): string
    {
        $config = $this->loader->getPhpMdConfig();
        $paths = $config->paths;

        $format = self::DEFAULT_PHP_MD_FORMAT;

        $command = escapeshellarg($binary)
            . ' ' . escapeshellarg(implode(',', $paths))
            . ' ' . escapeshellarg($format)
            . ' ' . escapeshellarg($target);

        $baseline = $config->baseline;

        if ($baseline !== null && $baseline !== '') {
            $command .= ' --baseline-file=' . escapeshellarg($baseline);
        }

        return $command;
    }

    private function buildRectorCommand(string $binary, string $target): string
    {
        return escapeshellarg($binary)
            . ' process --config=' . escapeshellarg($target)
            . ' --clear-cache';
    }

    private function buildPhpCsFixerCommand(string $binary, string $target): string
    {
        return escapeshellarg($binary)
            . ' fix --config=' . escapeshellarg($target)
            . ' --allow-risky=yes';
    }

    private function buildComposerUnusedCommand(string $binary, string $target): string
    {
        return escapeshellarg($binary)
            . ' --configuration=' . escapeshellarg($target);
    }

    private function buildComposerNormalizeCommand(): string
    {
        return 'composer normalize';
    }

    /**
     * @throws DirException
     */
    private function resolveGeneratedTarget(Tool $tool): string
    {
        return rtrim($this->loader->getComposerDir(), '/') . '/' . $tool->generatedTarget();
    }

    /**
     * @throws DirException
     */
    private function resolveBinary(string $binary): string
    {
        $path = rtrim($this->loader->getComposerDir(), '/') . '/vendor/bin/' . $binary;

        if (!file_exists($path)) {
            throw new RuntimeException(\sprintf('Unable to locate %s binary at %s', $binary, $path));
        }

        return $path;
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
