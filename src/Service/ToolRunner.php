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
use Symfony\Component\Console\Output\OutputInterface;

final readonly class ToolRunner
{
    private const DEFAULT_PHP_MD_FORMAT = 'text';

    public function __construct(
        private ConfigurationLoader $loader
    ) {
    }

    public function generate(Tool $tool): string
    {
        $target = $this->resolveGeneratedTarget($tool);
        $generator = $this->createGenerator($tool);
        $generator->generate($target);

        return $target;
    }

    public function run(Tool $tool, OutputInterface $output): int
    {
        $output->writeln('Generating: ' . $tool->label() . ' config');
        $target = $this->generate($tool);

        $command = $this->buildCommand($tool, $target);

        return $this->runCommand($output, $tool->label(), $command);
    }

    private function createGenerator(Tool $tool): ConfigGeneratorInterface
    {
        return match ($tool) {
            Tool::PHP_STAN => new PhpStanConfigGenerator($this->loader),
            Tool::PHP_CS => new PhpCsConfigGenerator($this->loader),
            Tool::PHP_MD => new PhpMdConfigGenerator($this->loader),
            Tool::RECTOR => new RectorConfigGenerator($this->loader),
            Tool::PHP_CS_FIXER => new PhpCsFixerConfigGenerator($this->loader),
            Tool::COMPOSER_UNUSED => new ComposerUnusedConfigGenerator($this->loader),
        };
    }

    private function buildCommand(Tool $tool, string $target): string
    {
        $binary = $this->resolveBinary($tool->binary());

        return match ($tool) {
            Tool::PHP_STAN => $this->buildPhpStanCommand($binary, $target),
            Tool::PHP_CS => $this->buildPhpCsCommand($binary, $target),
            Tool::PHP_MD => $this->buildPhpMdCommand($binary, $target),
            Tool::RECTOR => $this->buildRectorCommand($binary, $target),
            Tool::PHP_CS_FIXER => $this->buildPhpCsFixerCommand($binary, $target),
            Tool::COMPOSER_UNUSED => $this->buildComposerUnusedCommand($binary, $target),
        };
    }

    private function buildPhpStanCommand(string $binary, string $target): string
    {
        $command = escapeshellarg($binary) . ' analyze --configuration=' . escapeshellarg($target);

        $config = $this->loader->getPhpStanConfig();
        $parallel = $config->parallel;
        if ($parallel?->enabled) {
            $command .= ' --parallel';
            if ($parallel->maxProcesses !== null) {
                $command .= ' --jobs=' . $parallel->maxProcesses;
            }
        }

        return $command;
    }

    private function buildPhpCsCommand(string $binary, string $target): string
    {
        $command = escapeshellarg($binary) . ' --standard=' . escapeshellarg($target);

        $config = $this->loader->getPhpCsConfig();
        $parallel = $config->parallel;
        if ($parallel?->enabled && $parallel->maxProcesses !== null) {
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

    private function resolveGeneratedTarget(Tool $tool): string
    {
        return rtrim($this->loader->getComposerDir(), '/') . '/' . $tool->generatedTarget();
    }

    private function resolveBinary(string $binary): string
    {
        $path = rtrim($this->loader->getComposerDir(), '/') . '/vendor/bin/' . $binary;
        if (!file_exists($path)) {
            throw new RuntimeException("Unable to locate {$binary} binary at {$path}");
        }

        return $path;
    }

    private function runCommand(OutputInterface $output, string $label, string $command): int
    {
        $output->writeln("Running: {$label}");
        passthru($command, $exitCode);

        if ($exitCode !== 0) {
            $output->writeln("<error>{$label} failed with exit code {$exitCode}</error>");
        }

        return (int)$exitCode;
    }
}
