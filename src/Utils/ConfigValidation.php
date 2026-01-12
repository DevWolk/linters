<?php

declare(strict_types=1);

namespace Linters\Utils;

use InvalidArgumentException;

final class ConfigValidation
{
    /**
     * @return string[]
     */
    public static function stringList(string|array $value): array
    {
        $list = (array) $value;

        return array_filter($list, static fn(mixed $item): bool => \is_string($item));
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

    public static function optionalRelativePath(mixed $value, string $key): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new InvalidArgumentException($key . ' must be a string');
        }

        return $value;
    }

    /**
     * @return string[]
     */
    public static function normalizeFrameworks(mixed $frameworks): array
    {
        $list = self::optionalStringList($frameworks);
        $list = array_map(
            static fn(string $framework): string => strtolower($framework),
            $list
        );

        return array_values($list);
    }
}
