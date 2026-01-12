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
use Throwable;

final class RunCommand extends Command
{
    protected function configure(): void
    {
        $tools = implode('|', array_map(
            static fn(Tool $tool): string => $tool->value,
            Tool::cases()
        ));

        $this
            ->setName('run')
            ->setDescription('Generate config and run a tool from extra.linters')
            ->addArgument('tool', InputArgument::REQUIRED, $tools);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $toolName = strtolower((string)$input->getArgument('tool'));
            $tool = Tool::fromName($toolName);

            $loader = new ConfigurationLoader();
            $runner = new ToolRunner($loader);

            return $runner->run($tool, $output);
        } catch (Throwable $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
