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

        foreach ($list as $item) {
            if (!is_string($item)) {
                unset($item);
            }
        }

        return $list;
    }

    /**
     * @return string[]
     */
    public static function optionalStringList(mixed $value, string $key): array
    {
        if ($value === null) {
            return [];
        }

        return self::stringList($value, $key);
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
        $list = self::optionalStringList($frameworks, 'extra.linters.rector.frameworks');
        $list = array_map(
            static fn(string $framework): string => strtolower($framework),
            $list
        );

        return array_values($list);
    }
}
