<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    public function testIt(): void
    {
        $mock = $this->createMock(SomeService::class);
        $mock->expects($this->once())
            ->method('run');

        $mock->expects($this->any())
            ->method('handle');
    }
}
