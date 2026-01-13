<?php

declare(strict_types=1);

namespace Linters\Utils;

use InvalidArgumentException;
use Linters\Enum\RectorSet;

final class ConfigValidation
{
    public static function isNonEmptyString(?string $value): bool
    {
        return $value !== null && $value !== '';
    }

    /**
     * @param string|array<int|string, mixed> $value
     *
     * @return string[]
     */
    public static function stringList(string|array $value): array
    {
        return array_filter((array) $value, \is_string(...));
    }

    /**
     * @param string|array<int|string, mixed> $value
     *
     * @return string[]
     */
    public static function requiredPaths(string|array $value, string $toolName): array
    {
        $paths = self::stringList($value);

        if ($paths === []) {
            throw new InvalidArgumentException(
                \sprintf('Missing required config: extra.linters.%s.paths', $toolName)
            );
        }

        return $paths;
    }

    /**
     * @return string[]
     */
    public static function optionalStringList(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        return self::stringList($value);
    }

    /**
     * @return RectorSet[]
     */
    public static function normalizeSets(mixed $sets): array
    {
        $list = self::optionalStringList($sets);
        $result = [];

        foreach ($list as $set) {
            $enum = RectorSet::tryFrom(strtolower($set));

            if ($enum !== null) {
                $result[] = $enum;
            }
        }

        return $result;
    }
}
