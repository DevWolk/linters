<?php

declare(strict_types=1);

namespace Linters\Service;

use Linters\CommandBuilder\Contracts\ConfigurableCommandBuilderInterface;
use Linters\Enum\Tool;
use Linters\Utils\ConfigurationLoader;
use LogicException;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;

use function Safe\passthru;

final readonly class ToolRunner
{
    private ConfigGeneratorFactory $configGeneratorFactory;

    private CommandBuilderFactory $commandBuilderFactory;

    public function __construct(
        private ConfigurationLoader $loader,
        ?ConfigGeneratorFactory $configGeneratorFactory = null,
        ?CommandBuilderFactory $commandBuilderFactory = null,
    ) {
        $this->configGeneratorFactory = $configGeneratorFactory ?? new ConfigGeneratorFactory($loader);
        $this->commandBuilderFactory = $commandBuilderFactory ?? new CommandBuilderFactory($loader);
    }

    public function generate(Tool $tool): string
    {
        $target = $this->resolveGeneratedTarget($tool);
        $generator = $this->configGeneratorFactory->create($tool);
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

    /**
     * @param string[] $extraArgs
     */
    private function buildCommand(Tool $tool, ?string $configPath, array $extraArgs): string
    {
        $builder = $this->commandBuilderFactory->create($tool);

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

        if ($exitCode === null) {
            throw new RuntimeException(\sprintf('%s did not return an exit code', $label));
        }

        if ($exitCode !== 0) {
            $output->writeln(\sprintf('<error>%s failed with exit code %s</error>', $label, $exitCode));
        }

        return $exitCode;
    }
}
