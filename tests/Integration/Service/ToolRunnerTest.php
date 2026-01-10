<?php

declare(strict_types=1);

namespace Linters\Tests\Integration\Service;

use Linters\Enum\Tool;
use Linters\Service\ToolRunner;
use Linters\Utils\ConfigurationLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

final class ToolRunnerTest extends TestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDir = sys_get_temp_dir() . '/linters-runner-' . uniqid('', true);
        mkdir($this->testDir);
        mkdir($this->testDir . '/vendor');
        mkdir($this->testDir . '/vendor/bin');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (is_dir($this->testDir)) {
            $this->removeDirectory($this->testDir);
        }
    }

    public function testGenerateUsesOverrideTarget(): void
    {
        $this->createComposerJson([
            'phpstan' => [
                'paths' => ['/src'],
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);
        $runner = new ToolRunner($loader);

        $overrideTarget = $this->testDir . '/override.neon';
        $target = $runner->generate(Tool::PHP_STAN, $overrideTarget, '/template.neon');

        self::assertSame($overrideTarget, $target);
        self::assertFileExists($overrideTarget);
        self::assertStringContainsString('/template.neon', file_get_contents($overrideTarget));
    }

    public function testRunUsesFormatOverrideAndInvokesBinary(): void
    {
        $targetPath = $this->testDir . '/phpmd.ruleset.xml';
        $logPath = $this->testDir . '/phpmd.log';
        $binaryPath = $this->testDir . '/vendor/bin/phpmd';

        $this->createComposerJson([
            'phpmd' => [
                'paths' => ['/src', '/tests'],
                'target' => $targetPath,
                'format' => 'text',
            ],
        ]);

        $this->createBinaryScript($binaryPath, $logPath);

        $loader = new ConfigurationLoader($this->testDir);
        $runner = new ToolRunner($loader);
        $output = new BufferedOutput();

        $exitCode = $runner->run(Tool::PHP_MD, null, null, 'xml', $output);

        self::assertSame(0, $exitCode);
        self::assertFileExists($logPath);

        $loggedArgs = file($logPath, FILE_IGNORE_NEW_LINES);
        self::assertSame([
            $this->testDir . '/src,' . $this->testDir . '/tests',
            'xml',
            $targetPath,
        ], $loggedArgs);
    }

    private function createBinaryScript(string $binaryPath, string $logPath): void
    {
        $script = "#!/usr/bin/env sh\n";
        $script .= "printf '%s\\n' \"$@\" > '{$logPath}'\n";
        $script .= "exit 0\n";

        file_put_contents($binaryPath, $script);
        chmod($binaryPath, 0755);
    }

    private function createComposerJson(array $lintersConfig): void
    {
        $json = json_encode([
            'extra' => [
                'linters' => $lintersConfig,
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        file_put_contents($this->testDir . '/composer.json', $json);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
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
}
