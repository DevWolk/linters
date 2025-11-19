<?php

declare(strict_types=1);

namespace Linters\Rector\Set;

final class AppRectorSetList
{
    /**
     * Path to the custom rules set configuration file
     *
     * @var string
     */
    public const CUSTOM_RULES = __DIR__ . '/../../../configs/rector/custom-rules.php';

    /**
     * Path to the PHPUnit rules set configuration file
     *
     * @var string
     */
    public const PHPUNIT_RULES = __DIR__ . '/../../../configs/rector/phpunit-rules.php';

    /**
     * Path to the strict types rules set configuration file
     *
     * @var string
     */
    public const STRICT_TYPES = __DIR__ . '/../../../configs/rector/strict-types.php';

    /**
     * Path to the code quality rules set configuration file
     *
     * @var string
     */
    public const CODE_QUALITY = __DIR__ . '/../../../configs/rector/code-quality.php';
}
