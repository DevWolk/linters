<?php

declare(strict_types=1);

namespace Linters\Console\Command;

use Linters\Enum\Tool;
use Linters\Service\ToolRunner;
use Linters\Utils\ConfigurationLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class GenerateConfigCommand extends Command
{
    protected function configure(): void
    {
        $tools = implode('|', array_map(
            static fn(Tool $tool): string => $tool->value,
            array_filter(
                Tool::cases(),
                static fn(Tool $tool): bool => $tool->requiresGeneration()
            )
        ));

        $this
            ->setName('generate')
            ->setDescription('Generate tool configuration from extra.linters')
            ->addArgument('tool', InputArgument::REQUIRED, $tools);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $toolName = strtolower((string)$input->getArgument('tool'));
            $tool = Tool::fromName($toolName);
            if ($tool->requiresGeneration() === false) {
                throw new \RuntimeException($tool->label() . ' does not support config generation');
            }

            $loader = new ConfigurationLoader();
            $runner = new ToolRunner($loader);

            $target = $runner->generate($tool);
        } catch (\Throwable $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
            return Command::FAILURE;
        }

        $output->writeln("Generated: {$target}");
        return Command::SUCCESS;
    }
}
