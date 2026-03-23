<?php

declare(strict_types=1);

namespace Linters\Utils;

final readonly class ImportNamesOptions
{
    public function __construct(
        public bool $importNames = true,
        public bool $importDocBlockNames = false,
        public bool $importShortClasses = false,
        public bool $removeUnusedImports = true,
    ) {
    }

    /**
     * @param array<string, mixed>|null $value
     */
    public static function fromMixed(?array $value): self
    {
        if ($value === null) {
            return new self();
        }

        return new self(
            importNames: (bool) ($value['import-names'] ?? true),
            importDocBlockNames: (bool) ($value['import-doc-block-names'] ?? false),
            importShortClasses: (bool) ($value['import-short-classes'] ?? false),
            removeUnusedImports: (bool) ($value['remove-unused-imports'] ?? true),
        );
    }
}
