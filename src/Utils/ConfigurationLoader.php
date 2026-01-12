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

        $config = $content['extra'][$this->extraKey] ?? [];

        if (is_array($config) === false) {
            throw new RuntimeException('extra.' . $this->extraKey . ' must be an object');
        }

        $this->config = $config;
    }

    /**
     * @return array|string|null
     */
    public function get(string $keys, mixed $default = null): mixed
    {
        $explodedKeys = explode('.', $keys);
        $array = $this->config;
        foreach ($explodedKeys as $key) {
            if (\is_array($array) && array_key_exists($key, $array)) {
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
        $paths = array_values(array_filter(
            $paths,
            static fn(mixed $path): bool => is_string($path) && $path !== ''
        ));
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
