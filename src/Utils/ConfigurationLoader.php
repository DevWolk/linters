<?php

declare(strict_types=1);

namespace Linters\Utils;

use JsonException;
use Linters\DTO\ComposerUnusedConfig;
use Linters\DTO\PhpCsConfig;
use Linters\DTO\PhpCsFixerConfig;
use Linters\DTO\PhpMdConfig;
use Linters\DTO\PhpStanConfig;
use Linters\DTO\RectorConfig;
use Linters\Enum\Tool;
use RuntimeException;

class ConfigurationLoader
{
    /** @var string */
    protected const COMPOSER_FILE = '/composer.json';

    protected array $config = [];

    private const EXTRA_KEY = 'linters';

    /**
     * @throws JsonException
     */
    public function __construct(
        protected ?string $composerDir = null,
        protected string $extraKey = self::EXTRA_KEY,
    ) {
        $this->composerDir ??= getcwd();

        if (!file_exists($this->composerDir . self::COMPOSER_FILE)) {
            throw new RuntimeException(self::COMPOSER_FILE . ' file not found');
        }

        $content = file_get_contents($this->composerDir . self::COMPOSER_FILE);
        $content = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $config = $content['extra'][$this->extraKey] ?? [];

        if (!is_array($config)) {
            throw new RuntimeException('extra.' . $this->extraKey . ' must be an object');
        }

        $this->config = $config;
        $this->validateConfig();
    }

    /**
     * @return array|string|null
     */
    public function get(string $keys, mixed $default = null): mixed
    {
        $explodedKeys = explode('.', $keys);
        $array = $this->config;
        foreach ($explodedKeys as $key) {
            if (!is_array($array) || !array_key_exists($key, $array)) {
                return $default;
            }

            $array = $array[$key];
        }

        return $array;
    }

    public function getRectorConfig(): RectorConfig
    {
        return RectorConfig::fromArray($this->getToolConfig(Tool::RECTOR->value));
    }

    public function getPhpStanConfig(): PhpStanConfig
    {
        return PhpStanConfig::fromArray($this->getToolConfig(Tool::PHP_STAN->value));
    }

    public function getPhpCsConfig(): PhpCsConfig
    {
        return PhpCsConfig::fromArray($this->getToolConfig(Tool::PHP_CS->value));
    }

    public function getPhpMdConfig(): PhpMdConfig
    {
        return PhpMdConfig::fromArray($this->getToolConfig(Tool::PHP_MD->value));
    }

    public function getPhpCsFixerConfig(): PhpCsFixerConfig
    {
        return PhpCsFixerConfig::fromArray($this->getToolConfig(Tool::PHP_CS_FIXER->value));
    }

    public function getComposerUnusedConfig(): ComposerUnusedConfig
    {
        return ComposerUnusedConfig::fromArray($this->getToolConfig(Tool::COMPOSER_UNUSED->value));
    }

    public function getComposerDir(): string
    {
        return $this->composerDir ?? getcwd();
    }

    private function getToolConfig(string $tool): array
    {
        return $this->config[$tool];
    }

    private function validateConfig(): void
    {
        foreach ($this->config as $tool => $toolConfig) {
            if (!is_string($tool) || Tool::tryFrom($tool) === null) {
                throw new RuntimeException('Unsupported tool: extra.' . $this->extraKey . '.' . (string) $tool);
            }

            if (!is_array($toolConfig)) {
                throw new RuntimeException('extra.' . $this->extraKey . '.' . $tool . ' must be an object');
            }
        }
    }

}
