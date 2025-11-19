<?php

declare(strict_types=1);

namespace Linters\Utils;

use Exception;
use RuntimeException;
use function explode;
use function file_exists;
use function file_get_contents;
use function json_decode;
use function rtrim;

class ConfigurationLoader
{
    /** @var string */
    protected const COMPOSER_FILE = '/composer.json';

    protected array $config = [];

    /**
     * @throws Exception
     */
    public function __construct(
        protected ?string $composerDir = null,
        protected string $extraKey = 'linters',
    ) {
        $this->composerDir ??= getcwd();

        if (file_exists($this->composerDir . self::COMPOSER_FILE) === false) {
            throw new RuntimeException(self::COMPOSER_FILE . ' file not found');
        }

        $content = file_get_contents($this->composerDir . self::COMPOSER_FILE);
        $content = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $this->config = $content['extra'][$this->extraKey] ?? [];
    }

    /**
     * @return array|string|null
     */
    public function get(string $keys, mixed $default = null): mixed
    {
        $explodedKeys = explode('.', $keys);
        $array = $this->config;
        foreach ($explodedKeys as $key) {
            if (\is_array($array) && isset($array[$key])) {
                $array = $array[$key];
            } else {
                return $default;
            }
        }

        return $array;
    }

    public function getAbsolutePaths(string $key, array $default = []): array
    {
        $paths = (array)$this->get($key, $default);
        $root  = $this->getComposerDir();

        return array_map(
            static fn(string $path): string => rtrim($root, '/') . $path,
            $paths
        );
    }

    public function getComposerDir(): string
    {
        return (string)($this->composerDir ?? getcwd());
    }
}
