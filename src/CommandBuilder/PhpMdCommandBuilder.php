<?php

declare(strict_types=1);

namespace Linters\CommandBuilder;

use Linters\CommandBuilder\Contracts\AbstractConfigurableCommandBuilder;
use Linters\DTO\PhpMdConfig;
use Linters\Utils\ConfigValidation;
use Symfony\Component\Filesystem\Path;

final class PhpMdCommandBuilder extends AbstractConfigurableCommandBuilder
{
    private const string DEFAULT_FORMAT = 'text';

    public function build(array $extraArgs): string
    {
        $config = $this->loader->getPhpMdConfig();
        $paths = array_map(
            $this->escapeArg(...),
            $config->paths,
        );

        $command = $this->escapeArg($this->resolveBinary())
            . ' analyze ' . implode(' ', $paths)
            . ' --format ' . $this->escapeArg(self::DEFAULT_FORMAT)
            . ' --ruleset ' . $this->escapeArg($this->getConfigPath());

        $baseline = $config->baseline;

        if (ConfigValidation::isNonEmptyString($baseline)) {
            $command .= ' --baseline-file=' . $this->escapeArg($baseline);
        }

        if ($config->parallel?->enabled === true && $config->parallel->maxProcesses !== null) {
            $command .= ' --threads=' . $this->escapeArg((string) $config->parallel->maxProcesses);
        }

        if (ConfigValidation::isNonEmptyString($config->cacheDir)) {
            $cacheFile = Path::join($config->cacheDir, PhpMdConfig::CACHE_FILE_NAME);
            $command .= ' --cache --cache-file=' . $this->escapeArg($cacheFile);
        }

        return $command . $this->buildExtraArgs($extraArgs);
    }
}
