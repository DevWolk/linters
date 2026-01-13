<?php

declare(strict_types=1);

namespace Linters\Tests\Integration\Service;

use Linters\Enum\Tool;
use Linters\Service\ToolRunner;
use Linters\Tests\TestCase;
use Linters\Utils\ConfigurationLoader;
use Safe\Exceptions\DirException;
use Safe\Exceptions\FilesystemException;
use Safe\Exceptions\JsonException;
use Symfony\Component\Console\Output\BufferedOutput;

use function Safe\chmod;
use function Safe\file;
use function Safe\file_get_contents;
use function Safe\file_put_contents;
use function Safe\json_encode;
use function Safe\mkdir;

final class ToolRunnerTest extends TestCase
{
    private string $testDir;

    /**
     * @throws FilesystemException
     * @throws DirException
     * @throws \JsonException
     */
    public function testGenerateUsesDefaultTarget(): void
    {
        $this->createComposerJson([
            'phpstan' => [
                'paths' => ['src'],
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);
        $runner = new ToolRunner($loader);

        $target = $runner->generate(Tool::PHP_STAN);
        $expectedTarget = $this->testDir . '/phpstan.neon';

        self::assertSame($expectedTarget, $target);
        self::assertFileExists($expectedTarget);
        self::assertStringContainsString('configs/phpstan.neon', file_get_contents($expectedTarget));
    }

    /**
     * @throws FilesystemException
     * @throws DirException
     * @throws \JsonException
     */
    public function testRunInvokesBinaryWithDefaultFormat(): void
    {
        $logPath = $this->testDir . '/phpmd.log';
        $binaryPath = $this->testDir . '/vendor/bin/phpmd';

        $this->createComposerJson([
            'phpmd' => [
                'paths' => ['src', 'tests'],
            ],
        ]);

        $this->createBinaryScript($binaryPath, $logPath);

        $loader = new ConfigurationLoader($this->testDir);
        $runner = new ToolRunner($loader);
        $output = new BufferedOutput();

        $exitCode = $runner->run(Tool::PHP_MD, $output);

        self::assertSame(0, $exitCode);
        self::assertFileExists($logPath);

        $targetPath = $this->testDir . '/phpmd.ruleset.xml';
        $loggedArgs = file($logPath, FILE_IGNORE_NEW_LINES);
        self::assertSame([
            'src,tests',
            'text',
            $targetPath,
        ], $loggedArgs);
    }

    /**
     * @throws FilesystemException
     * @throws DirException
     * @throws \JsonException
     */
    public function testRunAddsPhpMdBaselineFile(): void
    {
        $logPath = $this->testDir . '/phpmd-baseline.log';
        $binaryPath = $this->testDir . '/vendor/bin/phpmd';

        $this->createComposerJson([
            'phpmd' => [
                'paths'    => ['src'],
                'baseline' => 'phpmd-baseline.xml',
            ],
        ]);

        $this->createBinaryScript($binaryPath, $logPath);

        $loader = new ConfigurationLoader($this->testDir);
        $runner = new ToolRunner($loader);
        $output = new BufferedOutput();

        $exitCode = $runner->run(Tool::PHP_MD, $output);

        self::assertSame(0, $exitCode);
        self::assertFileExists($logPath);

        $targetPath = $this->testDir . '/phpmd.ruleset.xml';
        $loggedArgs = file($logPath, FILE_IGNORE_NEW_LINES);
        self::assertSame([
            'src',
            'text',
            $targetPath,
            '--baseline-file=phpmd-baseline.xml',
        ], $loggedArgs);
    }

    /**
     * @throws FilesystemException
     * @throws DirException
     * @throws \JsonException
     */
    public function testRunAddsPhpCsParallelFlag(): void
    {
        $logPath = $this->testDir . '/phpcs.log';
        $binaryPath = $this->testDir . '/vendor/bin/phpcs';

        $this->createComposerJson([
            'phpcs' => [
                'paths'    => ['src'],
                'parallel' => [
                    'enabled'       => true,
                    'max_processes' => 4,
                ],
            ],
        ]);

        $this->createBinaryScript($binaryPath, $logPath);

        $loader = new ConfigurationLoader($this->testDir);
        $runner = new ToolRunner($loader);
        $output = new BufferedOutput();

        $exitCode = $runner->run(Tool::PHP_CS, $output);

        self::assertSame(0, $exitCode);
        self::assertFileExists($logPath);

        $targetPath = $this->testDir . '/phpcs.xml';
        $loggedArgs = file($logPath, FILE_IGNORE_NEW_LINES);
        self::assertSame([
            '--standard=' . $targetPath,
            '--parallel=4',
        ], $loggedArgs);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDir = $this->createTempDir('linters-runner-');
        mkdir($this->testDir . '/vendor');
        mkdir($this->testDir . '/vendor/bin');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeDirectory($this->testDir);
    }

    /**
     * @throws FilesystemException
     */
    private function createBinaryScript(string $binaryPath, string $logPath): void
    {
        $script = "#!/usr/bin/env sh\n";
        $script .= "printf '%s\\n' \"$@\" > '{$logPath}'\n";
        $script .= "exit 0\n";

        file_put_contents($binaryPath, $script);
        chmod($binaryPath, 0755);
    }

    /**
     * @param array<string, mixed> $lintersConfig
     *
     * @throws FilesystemException
     * @throws JsonException
     */
    private function createComposerJson(array $lintersConfig): void
    {
        $json = json_encode([
            'extra' => [
                'linters' => $lintersConfig,
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        file_put_contents($this->testDir . '/composer.json', $json);
    }
}
