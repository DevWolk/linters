<?php

declare(strict_types=1);

namespace Linters\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Safe\Exceptions\DirException;
use Safe\Exceptions\FilesystemException;
use Safe\Exceptions\JsonException;

use function Safe\file_put_contents;
use function Safe\json_encode;
use function Safe\mkdir;
use function Safe\rmdir;
use function Safe\scandir;
use function Safe\unlink;

abstract class TestCase extends BaseTestCase
{
    protected string $testDir;

    /**
     * @throws FilesystemException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->testDir = $this->createTempDir();
    }

    /**
     * @throws FilesystemException
     * @throws DirException
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeDirectory($this->testDir);
    }

    /**
     * @throws FilesystemException
     * @throws DirException
     */
    protected function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);

        foreach ($items as $item) {
            if ($item === '.') {
                continue;
            }

            if ($item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    /**
     * @throws FilesystemException
     */
    protected function createTempDir(string $prefix = 'linters-test-'): string
    {
        $dir = sys_get_temp_dir() . '/' . $prefix . uniqid('', true);
        mkdir($dir);

        return $dir;
    }

    /**
     * @param array<string, mixed> $lintersConfig
     *
     * @throws JsonException
     * @throws FilesystemException
     */
    protected function createComposerJson(array $lintersConfig): void
    {
        $json = json_encode([
            'extra' => [
                'linters' => $lintersConfig,
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        file_put_contents($this->testDir . '/composer.json', $json);
    }
}
