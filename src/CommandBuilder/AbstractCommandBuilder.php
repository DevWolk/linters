<?php

declare(strict_types=1);

namespace Linters\CommandBuilder;

use Linters\Enum\Tool;
use Linters\Utils\ConfigurationLoader;
use Symfony\Component\Filesystem\Path;

abstract class AbstractCommandBuilder implements CommandBuilderInterface
{
    public function __construct(
        protected Tool $tool,
        protected ConfigurationLoader $loader,
    ) {
    }

    protected function resolveBinary(): string
    {
        return Path::join($this->loader->getComposerDir(), 'vendor', 'bin', $this->tool->value);
    }

    /**
     * @param string[] $args
     */
    protected function buildExtraArgs(array $args): string
    {
        $stringArgs = array_filter($args, \is_string(...));

        if ($stringArgs === []) {
            return '';
        }

        $escaped = array_map(escapeshellarg(...), $stringArgs);

        return ' ' . implode(' ', $escaped);
    }

    protected function escapeArg(string $arg): string
    {
        return escapeshellarg($arg);
    }
}
