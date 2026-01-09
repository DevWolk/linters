<?php

declare(strict_types=1);

namespace Linters\Service;

use Linters\ConfigGenerator\PhpCsConfigGenerator;
use Linters\ConfigGenerator\PhpMdConfigGenerator;
use Linters\ConfigGenerator\PhpStanConfigGenerator;
use Linters\Enum\Tool;
use Linters\Utils\ConfigurationLoader;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class ToolRunner
{
    public function __construct(
        private ConfigurationLoader $loader
    ) {
    }

    public function generate(Tool $tool, ?string $targetOverride, ?string $templateOverride): string
    {
        $target = $this->resolveTarget($tool, $targetOverride);
        $template = $this->resolveTemplate($tool, $templateOverride);

        $generator = $this->createGenerator($tool, $template);
        $generator->generate($target);

        return $target;
    }

    public function run(
        Tool $tool,
        ?string $targetOverride,
        ?string $templateOverride,
        ?string $formatOverride,
        OutputInterface $output
    ): int {
        if ($tool->requiresGeneration()) {
            $output->writeln('Generating: ' . $tool->label() . ' config');
            $target = $this->generate($tool, $targetOverride, $templateOverride);
        } else {
            $target = $this->resolveTarget($tool, $targetOverride);
        }

        $command = $this->buildCommand($tool, $target, $formatOverride);
        return $this->runCommand($output, $tool->label(), $command);
    }

    private function createGenerator(Tool $tool, ?string $template): PhpStanConfigGenerator|PhpCsConfigGenerator|PhpMdConfigGenerator
    {
        return match ($tool) {
            Tool::PHP_STAN => new PhpStanConfigGenerator($this->loader, $template),
            Tool::PHP_CS => new PhpCsConfigGenerator($this->loader, $template),
            Tool::PHP_MD => new PhpMdConfigGenerator($this->loader, $template),
        };
    }

    private function buildCommand(Tool $tool, string $target, ?string $formatOverride): string
    {
        $binary = $this->resolveBinary($tool->binary());

        return match ($tool) {
            Tool::PHP_STAN => escapeshellarg($binary) . ' analyze --configuration=' . escapeshellarg($target),
            Tool::PHP_CS => escapeshellarg($binary) . ' --standard=' . escapeshellarg($target),
            Tool::PHP_MD => $this->buildPhpMdCommand($binary, $target, $formatOverride),
        };
    }

    private function buildPhpMdCommand(string $binary, string $target, ?string $formatOverride): string
    {
        $paths = $this->loader->getAbsolutePaths('phpmd.paths');
        if ($paths === []) {
            throw new \RuntimeException('Missing required config: extra.linters.phpmd.paths');
        }

        $format = $formatOverride ?? $this->requireString('phpmd.format');

        return escapeshellarg($binary)
            . ' ' . escapeshellarg(implode(',', $paths))
            . ' ' . escapeshellarg($format)
            . ' ' . escapeshellarg($target);
    }

    private function resolveTarget(Tool $tool, ?string $targetOverride): string
    {
        $target = $this->normalizeOverride($targetOverride);
        if ($target !== null) {
            return $target;
        }

        return $this->requireString($tool->targetKey());
    }

    private function resolveTemplate(Tool $tool, ?string $templateOverride): ?string
    {
        $template = $this->normalizeOverride($templateOverride);
        if ($template !== null) {
            return $template;
        }

        $configTemplate = $this->loader->get($tool->templateKey());
        if (!is_string($configTemplate) || $configTemplate === '') {
            return null;
        }

        return $configTemplate;
    }

    private function resolveBinary(string $binary): string
    {
        $path = rtrim($this->loader->getComposerDir(), '/') . '/vendor/bin/' . $binary;
        if (file_exists($path) === false) {
            throw new \RuntimeException("Unable to locate {$binary} binary at {$path}");
        }

        return $path;
    }

    private function requireString(string $key): string
    {
        $value = $this->loader->get($key);
        if (!is_string($value) || $value === '') {
            throw new \RuntimeException("Missing required config: extra.linters.{$key}");
        }

        return $value;
    }

    private function normalizeOverride(?string $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        return $value;
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
