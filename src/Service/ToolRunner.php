<?php

declare(strict_types=1);

namespace Linters\Service;

use Linters\CommandBuilder\ComposerNormalizeCommandBuilder;
use Linters\CommandBuilder\ComposerUnusedCommandBuilder;
use Linters\CommandBuilder\Contracts\CommandBuilderInterface;
use Linters\CommandBuilder\Contracts\ConfigurableCommandBuilderInterface;
use Linters\CommandBuilder\PhpCsCommandBuilder;
use Linters\CommandBuilder\PhpCsFixerCommandBuilder;
use Linters\CommandBuilder\PhpMdCommandBuilder;
use Linters\CommandBuilder\PhpStanCommandBuilder;
use Linters\CommandBuilder\RectorCommandBuilder;
use Linters\ConfigGenerator\ComposerUnusedConfigGenerator;
use Linters\ConfigGenerator\Contracts\ConfigGeneratorInterface;
use Linters\ConfigGenerator\PhpCsConfigGenerator;
use Linters\ConfigGenerator\PhpCsFixerConfigGenerator;
use Linters\ConfigGenerator\PhpMdConfigGenerator;
use Linters\ConfigGenerator\PhpStanConfigGenerator;
use Linters\ConfigGenerator\RectorConfigGenerator;
use Linters\Enum\Tool;
use Linters\Utils\ConfigurationLoader;
use LogicException;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;

final readonly class ToolRunner
{
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
     */
    public function run(Tool $tool, OutputInterface $output, array $extraArgs = []): int
    {
        $configPath = null;

        if ($tool->requiresGeneration()) {
            $output->writeln('Generating: ' . $tool->label() . ' config');
            $configPath = $this->generate($tool);
        }

        $command = $this->buildCommand($tool, $configPath, $extraArgs);

        return $this->runCommand($output, $tool->label(), $command);
    }

    private function createGenerator(Tool $tool): ConfigGeneratorInterface
    {
        return match ($tool) {
            Tool::PHP_STAN => new PhpStanConfigGenerator($this->loader),
            Tool::PHP_CS, Tool::PHP_CBF => new PhpCsConfigGenerator($this->loader),
            Tool::PHP_MD => new PhpMdConfigGenerator($this->loader),
            Tool::RECTOR => new RectorConfigGenerator($this->loader),
            Tool::PHP_CS_FIXER => new PhpCsFixerConfigGenerator($this->loader),
            Tool::COMPOSER_UNUSED => new ComposerUnusedConfigGenerator($this->loader),
            Tool::COMPOSER_NORMALIZE => throw new RuntimeException('No generator for this tool'),
        };
    }

    private function createCommandBuilder(Tool $tool): CommandBuilderInterface
    {
        return match ($tool) {
            Tool::PHP_STAN => new PhpStanCommandBuilder($tool, $this->loader),
            Tool::PHP_CS, Tool::PHP_CBF => new PhpCsCommandBuilder($tool, $this->loader),
            Tool::PHP_MD => new PhpMdCommandBuilder($tool, $this->loader),
            Tool::RECTOR => new RectorCommandBuilder($tool, $this->loader),
            Tool::PHP_CS_FIXER => new PhpCsFixerCommandBuilder($tool, $this->loader),
            Tool::COMPOSER_UNUSED => new ComposerUnusedCommandBuilder($tool, $this->loader),
            Tool::COMPOSER_NORMALIZE => new ComposerNormalizeCommandBuilder($tool, $this->loader),
        };
    }

    /**
     * @param string[] $extraArgs
     */
    private function buildCommand(Tool $tool, ?string $configPath, array $extraArgs): string
    {
        $builder = $this->createCommandBuilder($tool);

        if ($configPath !== null && $builder instanceof ConfigurableCommandBuilderInterface) {
            $builder->setConfigPath($configPath);
        } elseif ($configPath !== null) {
            throw new LogicException(\sprintf(
                'Tool %s generated a config but its builder does not implement ConfigurableCommandBuilderInterface',
                $tool->value,
            ));
        }

        return $builder->build($extraArgs);
    }

    private function resolveGeneratedTarget(Tool $tool): string
    {
        $target = $tool->generatedTarget();

        if ($target === null) {
            throw new RuntimeException(\sprintf('Tool %s does not have a generated target', $tool->value));
        }

        return Path::join($this->loader->getComposerDir(), $target);
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
