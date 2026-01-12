# TODO

## 1) Fix config validation and required keys
- Problem: `ConfigValidation::optionalStringList()` calls `stringList()` with a wrong signature.
- Plan:
  1. Fix `ConfigValidation::optionalStringList()` to remove string $key.
  2. Adjust tests

## 2) Enforce the "only 7 allowed config points" rule
- Problem: unknown keys inside `extra.linters.<tool>` are silently ignored, which violates the "no extra knobs" requirement.
- Plan:
  1. This is not an issue for now. Do nothing.

## 3) Align docs and examples with the actual schema
- Problem: docs/examples/README still mention unsupported keys (`skip`, `level`, `target`, `format`, `rulesets`, `config`, `template`) and require leading `/` paths, which the code does not enforce.
- Plan:
  1. Update `docs/INSTALLATION.md`, `docs/CONFIGURATION.md`, `docs/PHPSTAN_GUIDE.md`, `docs/RECTOR_GUIDE.md`, `docs/TROUBLESHOOTING.md` to only show supported keys.
  2. Replace `skip` with `skip_dirs`/`skip_files` everywhere.
  3. Clarify that paths are used as-is (relative or absolute) and no normalization is applied.
  4. Update example composer.json files and READMEs under `examples/` to match the real schema.

## 4) Finish Symfony framework preset handling
- Problem: `src/Rector/Configs/Sets/symfony.php` is empty, but `symfony` is exposed as a framework option.
- Plan:
  1. Remove the `symfony` framework option from docs/README.

## 5) Strengthen custom Rector rule tests
- Problem: fixtures in `tests/Integration/Rector/.../Fixture/*.php.inc` only show the "after" state, so transformations are not actually verified.
- Plan:
  1. Convert fixtures to the standard Rector "before/after" format with `-----`.
  2. Add at least one fixture that demonstrates each rule's actual transformation.

## 6) Improve missing-tool config errors
- Problem: `ConfigurationLoader::getToolConfig()` assumes the tool exists and can trigger PHP notices instead of a clear exception.
- Plan:
  1. This is not an issue for now. Cause is already handled in `\Linters\Utils\ConfigurationLoader::validateConfig` for the step when config is loaded in the constructor.
