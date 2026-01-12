<?php

declare(strict_types=1);

namespace Linters\DTO;

final readonly class ParallelConfig
{
    public function __construct(
        public bool $enabled,
        public ?int $timeout = null,
        public ?int $maxProcesses = null,
        public ?int $filesPerProcess = null,
    ) {
    }

    public static function fromMixed(null|bool|string|int|array $value, bool $defaultEnabled = false): self
    {
        if (is_bool($value)) {
            return new self($value);
        }

        if (is_numeric($value)) {
            return new self(true, maxProcesses: (int)$value);
        }

        if (is_array($value)) {
            return new self(
                enabled: (bool) ($value['enabled'] ?? $defaultEnabled),
                timeout: self::toInt($value['timeout'] ?? null),
                maxProcesses: self::toInt($value['max_processes'] ?? null),
                filesPerProcess: self::toInt($value['files_per_process'] ?? null),
            );
        }

        return new self($defaultEnabled);
    }

    private static function toInt(string|int|null $value): ?int
    {
        if (is_numeric($value)) {
            return (int)$value;
        }

        return null;
    }
}
