<?php

declare(strict_types=1);

namespace Linters\Tests\Unit\Enum;

use Linters\Enum\RectorSet;
use Linters\Tests\TestCase;

final class RectorSetTest extends TestCase
{
    public function testGetPathReturnsValidPathForEachCase(): void
    {
        foreach (RectorSet::cases() as $case) {
            $path = $case->getPath();
            self::assertFileExists($path, \sprintf('File for %s does not exist', $case->name));
        }
    }
}
