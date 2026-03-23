<?php

declare(strict_types=1);

namespace Linters\Console\Command\Contracts;

use Linters\Enum\Tool;
use Linters\Service\ToolRunner;
use Linters\Utils\ConfigurationLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

abstract class AbstractToolCommand extends Command
{
    private InputInterface $input;

    abstract protected function getCommandName(): string;

    abstract protected function getCommandDescription(): string;

    abstract protected function doExecute(
        Tool $tool,
        ToolRunner $runner,
        OutputInterface $output,
    ): int;

    protected function configure(): void
    {
        $tools = implode('|', array_map(
            static fn (Tool $tool): string => $tool->value,
            Tool::cases(),
        ));

        $this
            ->setName($this->getCommandName())
            ->setDescription($this->getCommandDescription())
            ->addArgument('tool', InputArgument::REQUIRED, $tools)
            ->addArgument('extra', InputArgument::IS_ARRAY, 'Extra arguments to pass to the tool (use -- before them)')
            ->setHelp(<<<'HELP'
                Usage examples:
                  linters run phpstan
                  linters run rector -- --dry-run
                  linters generate phpcs

                Extra arguments after -- are passed directly to the underlying tool.
                HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->input = $input;
            $toolName = strtolower((string) $input->getArgument('tool'));
            $tool = Tool::tryFrom($toolName);

            if ($tool === null) {
                $available = implode(', ', array_map(
                    static fn (Tool $t): string => $t->value,
                    Tool::cases(),
                ));

                throw new \InvalidArgumentException(
                    \sprintf('Unknown tool "%s". Available tools: %s', $toolName, $available)
                );
            }

            $loader = new ConfigurationLoader();
            $runner = new ToolRunner($loader);

            return $this->doExecute($tool, $runner, $output);
        } catch (Throwable $throwable) {
            $output->writeln('<error>' . $throwable->getMessage() . '</error>');

            if ($output->isVerbose()) {
                $output->writeln('<error>' . $throwable->getTraceAsString() . '</error>');
            }

            return Command::FAILURE;
        }
    }

    /**
     * Get extra arguments passed after -- separator.
     *
     * @return string[]
     */
    protected function getExtraArgs(): array
    {
        return $this->input->getArgument('extra');
    }
}
