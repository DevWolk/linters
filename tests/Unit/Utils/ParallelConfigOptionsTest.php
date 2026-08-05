<?php

declare(strict_types=1);

namespace Linters\Tests\Unit\Utils;

use Generator;
use Linters\Enum\ParallelConfigDefault;
use Linters\Tests\TestCase;
use Linters\Utils\ParallelConfigOptions;
use PHPUnit\Framework\Attributes\DataProvider;

final class ParallelConfigOptionsTest extends TestCase
{
    /**
     * @param bool|int|array<string, mixed>|null        $value
     * @param array{bool, int|null, int|null, int|null} $expected
     */
    #[DataProvider('provideConfigurationValues')]
    public function testFromMixedNormalizesConfiguration(
        null|bool|int|array $value,
        ParallelConfigDefault $default,
        array $expected,
    ): void {
        $result = ParallelConfigOptions::fromMixed($value, $default);

        self::assertSame($expected, [
            $result->enabled,
            $result->timeout,
            $result->maxProcesses,
            $result->filesPerProcess,
        ]);
    }

    /**
     * @return Generator<
     *     string,
     *     array{bool|int|array<string, mixed>|null, ParallelConfigDefault, array{bool, int|null, int|null, int|null}}
     * >
     */
    public static function provideConfigurationValues(): Generator
    {
        yield 'disabled by default' => [null, ParallelConfigDefault::DISABLED, [false, null, null, null]];
        yield 'enabled by default' => [null, ParallelConfigDefault::ENABLED, [true, null, null, null]];
        yield 'boolean' => [true, ParallelConfigDefault::DISABLED, [true, null, null, null]];
        yield 'process count' => [4, ParallelConfigDefault::DISABLED, [true, null, 4, null]];
        yield 'full options' => [
            [
                'enabled' => true,
                'timeout' => '360',
                'max-processes' => '8',
                'files-per-process' => '40',
            ],
            ParallelConfigDefault::DISABLED,
            [true, 360, 8, 40],
        ];
    }
}
