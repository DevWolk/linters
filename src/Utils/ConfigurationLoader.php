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
use Safe\Exceptions\DirException;
use Safe\Exceptions\FilesystemException;

use function Safe\file_get_contents;
use function Safe\getcwd;

final readonly class ConfigurationLoader
{
    private const string COMPOSER_FILE = '/composer.json';

    private const string EXTRA_KEY = 'linters';

    private string $composerDir;

    /** @var array<string, array<string, mixed>> */
    private array $config;

    /**
     * @throws JsonException
     * @throws DirException
     * @throws FilesystemException
     */
    public function __construct(
        ?string $composerDir = null,
        private string $extraKey = self::EXTRA_KEY,
    ) {
        $this->composerDir = $composerDir ?? getcwd();

        if (!file_exists($this->composerDir . self::COMPOSER_FILE)) {
            throw new RuntimeException(self::COMPOSER_FILE . ' file not found');
        }

        $content = file_get_contents($this->composerDir . self::COMPOSER_FILE);
        $content = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $config = $content['extra'][$this->extraKey] ?? [];

        if (!\is_array($config)) {
            throw new RuntimeException('extra.' . $this->extraKey . ' must be an object');
        }

        $this->config = $config;
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
        return $this->composerDir;
    }

    /**
     * @return array<string, mixed>
     */
    private function getToolConfig(string $tool): array
    {
        return $this->config[$tool] ?? [];
    }
}
