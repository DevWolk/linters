<?php

declare(strict_types=1);

namespace Linters\Service;

use Linters\ConfigGenerator\ConfigGeneratorInterface;
use Linters\ConfigGenerator\PhpCsConfigGenerator;
use Linters\ConfigGenerator\PhpMdConfigGenerator;
use Linters\ConfigGenerator\PhpStanConfigGenerator;
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
        if (!$tool->requiresGeneration()) {
            throw new RuntimeException($tool->label() . ' does not require config generation');
        }

        $target = $this->resolveGeneratedTarget($tool);
        $generator = $this->createGenerator($tool);
        $generator->generate($target);

        return $target;
    }

    public function run(Tool $tool, OutputInterface $output): int
    {
        $target = null;
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
            Tool::PHP_STAN => new PhpStanConfigGenerator($this->loader),
            Tool::PHP_CS => new PhpCsConfigGenerator($this->loader),
            Tool::PHP_MD => new PhpMdConfigGenerator($this->loader),
            default => throw new RuntimeException('Unsupported generator tool: ' . $tool->label()),
        };
    }

    private function buildCommand(Tool $tool, ?string $target): string
    {
        $binary = $this->resolveBinary($tool->binary());

        return match ($tool) {
            Tool::PHP_STAN => $this->buildPhpStanCommand($binary, $target),
            Tool::PHP_CS => $this->buildPhpCsCommand($binary, $target),
            Tool::PHP_MD => $this->buildPhpMdCommand($binary, $target),
            Tool::RECTOR => $this->buildRectorCommand($binary),
            Tool::PHP_CS_FIXER => $this->buildPhpCsFixerCommand($binary),
            Tool::COMPOSER_UNUSED => $this->buildComposerUnusedCommand($binary),
        };
    }

    private function buildPhpStanCommand(string $binary, ?string $target): string
    {
        $target = $this->requireGeneratedTarget($target, Tool::PHP_STAN);

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

    private function buildPhpCsCommand(string $binary, ?string $target): string
    {
        $target = $this->requireGeneratedTarget($target, Tool::PHP_CS);

        $command = escapeshellarg($binary) . ' --standard=' . escapeshellarg($target);

        $config = $this->loader->getPhpCsConfig();
        $parallel = $config->parallel;
        if ($parallel?->enabled && $parallel->maxProcesses !== null) {
            $command .= ' --parallel=' . $parallel->maxProcesses;
        }

        return $command;
    }

    private function buildPhpMdCommand(string $binary, ?string $target): string
    {
        $target = $this->requireGeneratedTarget($target, Tool::PHP_MD);

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

    private function buildRectorCommand(string $binary): string
    {
        $configPath = $this->resolvePackageConfigPath(Tool::RECTOR);

        return escapeshellarg($binary)
            . ' process --config=' . escapeshellarg($configPath)
            . ' --clear-cache';
    }

    private function buildPhpCsFixerCommand(string $binary): string
    {
        $configPath = $this->resolvePackageConfigPath(Tool::PHP_CS_FIXER);

        return escapeshellarg($binary)
            . ' fix --config=' . escapeshellarg($configPath)
            . ' --allow-risky=yes';
    }

    private function buildComposerUnusedCommand(string $binary): string
    {
        $configPath = $this->resolvePackageConfigPath(Tool::COMPOSER_UNUSED);

        return escapeshellarg($binary)
            . ' --configuration=' . escapeshellarg($configPath);
    }

    private function resolveGeneratedTarget(Tool $tool): string
    {
        $relativePath = $tool->generatedTarget();

        if ($relativePath === null) {
            throw new RuntimeException('Missing generated target mapping for ' . $tool->label());
        }

        return rtrim($this->loader->getComposerDir(), '/') . '/' . $relativePath;
    }

    private function resolvePackageConfigPath(Tool $tool): string
    {
        $relativePath = $tool->packageConfigPath();

        if ($relativePath === null) {
            throw new RuntimeException('Missing package config mapping for ' . $tool->label());
        }

        return rtrim(dirname(__DIR__, 2), '/') . '/' . $relativePath;
    }

    private function resolveBinary(string $binary): string
    {
        $path = rtrim($this->loader->getComposerDir(), '/') . '/vendor/bin/' . $binary;
        if (!file_exists($path)) {
            throw new RuntimeException("Unable to locate {$binary} binary at {$path}");
        }

        return $path;
    }

    private function requireGeneratedTarget(?string $target, Tool $tool): string
    {
        if (!is_string($target) || $target === '') {
            throw new RuntimeException('Missing generated target for ' . $tool->label());
        }

        return $target;
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
