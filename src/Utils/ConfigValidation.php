<?php

declare(strict_types=1);

namespace Linters\Utils;

final class ConfigValidation
{
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
     * @return string[]
     */
    public static function normalizeFrameworks(mixed $frameworks): array
    {
        $list = self::optionalStringList($frameworks);
        $list = array_map(
            strtolower(...),
            $list,
        );

        return array_values($list);
    }
}
