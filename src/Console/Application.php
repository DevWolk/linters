<?php

declare(strict_types=1);

namespace Linters\Console;

use JsonException;
use Linters\Console\Command\GenerateConfigCommand;
use Linters\Console\Command\RunCommand;
use Symfony\Component\Console\Application as SymfonyApplication;

final class Application extends SymfonyApplication
{
    private const COMPOSER_JSON_PATH = __DIR__ . '/../../composer.json';

    /**
     * @throws JsonException
     */
    public function __construct()
    {
        parent::__construct('Linters', self::getVersionFromComposerJson());

        $this->add(new GenerateConfigCommand());
        $this->add(new RunCommand());
    }

    /**
     * @throws JsonException
     */
    private static function getVersionFromComposerJson(): string
    {
        if (!file_exists(self::COMPOSER_JSON_PATH)) {
            return 'UNKNOWN';
        }

        $content = file_get_contents(self::COMPOSER_JSON_PATH);
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        return $data['version'] ?? 'UNKNOWN';
    }
}
