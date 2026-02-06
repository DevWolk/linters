<?php

declare(strict_types=1);

namespace Linters\Utils;

final readonly class ParallelConfigOptions
{
    public function __construct(
        public bool $enabled,
        public ?int $timeout = null,
        public ?int $maxProcesses = null,
        public ?int $filesPerProcess = null,
    ) {
    }

    /**
     * @param bool|int|array<string, mixed>|null $value
     */
    public static function fromMixed(null|bool|int|array $value, bool $defaultEnabled = false): self
    {
        if ($value === null) {
            return new self($defaultEnabled);
        }

        if (\is_bool($value)) {
            return new self($value);
        }

        if (is_numeric($value)) {
            return new self(true, maxProcesses: $value);
        }

        return self::fromArray($value, $defaultEnabled);
    }

    /**
     * @param array<string, mixed> $value
     */
    private static function fromArray(array $value, bool $defaultEnabled): self
    {
        return new self(
            enabled: (bool) ($value['enabled'] ?? $defaultEnabled),
            timeout: self::toInt($value['timeout'] ?? null),
            maxProcesses: self::toInt($value['max-processes'] ?? null),
            filesPerProcess: self::toInt($value['files-per-process'] ?? null),
        );
    }

    private static function toInt(null|string|int $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
