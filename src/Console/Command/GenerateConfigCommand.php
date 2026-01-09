<?php

declare(strict_types=1);

namespace Linters\Console\Command;

use Linters\Enum\Tool;
use Linters\Service\ToolRunner;
use Linters\Utils\ConfigurationLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class GenerateConfigCommand extends Command
{
    protected function configure(): void
    {
        $tools = implode('|', array_map(
            static fn(Tool $tool): string => $tool->value,
            Tool::cases()
        ));

        $this
            ->setName('generate')
            ->setDescription('Generate tool configuration from extra.linters')
            ->addArgument('tool', InputArgument::REQUIRED, $tools)
            ->addOption('target', null, InputOption::VALUE_REQUIRED, 'Target output file')
            ->addOption('config', null, InputOption::VALUE_REQUIRED, 'Template file to use');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $toolName = strtolower((string)$input->getArgument('tool'));
            $tool = Tool::fromName($toolName);

            $loader = new ConfigurationLoader();
            $runner = new ToolRunner($loader);

            $target = $runner->generate(
                $tool,
                $this->normalizeOption($input->getOption('target')),
                $this->normalizeOption($input->getOption('config'))
            );
        } catch (\Throwable $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
            return Command::FAILURE;
        }

        $output->writeln("Generated: {$target}");
        return Command::SUCCESS;
    }

    private function normalizeOption(mixed $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
