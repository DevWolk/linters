<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    public function testIt(): void
    {
        self::assertInstanceOf(\stdClass::class, new \stdClass());
        self::assertTrue(true);
        self::assertSame(1, 1);
    }
}
