<?php

declare(strict_types=1);

use Linters\Rector\Set\AppRectorSetList;
use Linters\Utils\ConfigurationLoader;
use Linters\Utils\ConfigValidation;
use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\CodeQuality\Rector\ClassMethod\LocallyCalledStaticMethodToNonStaticRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodeQuality\Rector\Identical\SimplifyBoolIdenticalTrueRector;
use Rector\CodeQuality\Rector\Isset_\IssetOnPropertyObjectToPropertyExistsRector;
use Rector\CodingStyle\Rector\ClassMethod\MakeInheritedMethodVisibilitySameAsParentRector;
use Rector\CodingStyle\Rector\PostInc\PostIncDecToPreIncDecRector;
use Rector\CodingStyle\Rector\Use_\SeparateMultiUseImportsRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\StaticCall\RemoveParentCallWithoutParentRector;
use Rector\Naming\Rector\Assign\RenameVariableToMatchMethodCallReturnTypeRector;
use Rector\Naming\Rector\Class_\RenamePropertyToMatchTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameParamToMatchTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameVariableToMatchNewTypeRector;
use Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchExprVariableRector;
use Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchMethodCallReturnTypeRector;
use Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector;
use Rector\Php73\Rector\BooleanOr\IsCountableRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php74\Rector\Property\RestoreDefaultNullToNullableTypePropertyRector;
use Rector\Php84\Rector\FuncCall\AddEscapeArgumentRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector;
use Rector\PHPUnit\CodeQuality\Rector\ClassMethod\BareCreateMockAssignToDirectUseRector;
use Rector\PHPUnit\CodeQuality\Rector\ClassMethod\NoSetupWithParentCallOverrideRector;
use Rector\PHPUnit\PHPUnit120\Rector\CallLike\CreateStubOverCreateMockArgRector;
use Rector\PHPUnit\PHPUnit120\Rector\Class_\PropertyCreateMockToCreateStubRector;
use Rector\PHPUnit\PHPUnit120\Rector\ClassMethod\ExpressionCreateMockToCreateStubRector;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\Class_\TypedPropertyFromCreateMockAssignRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddMethodCallBasedStrictParamTypeRector;

$composerLoader = new ConfigurationLoader();
$config = $composerLoader->getRectorConfig();

$configuredSets = [
    AppRectorSetList::APP_RULES,

    SetList::CODE_QUALITY,
    SetList::CODING_STYLE,
    SetList::TYPE_DECLARATION,
    SetList::EARLY_RETURN,

    $config->phpVersion->getLevelSetList(),
];

foreach ($config->sets as $set) {
    $path = $set->getPath();
    if (!in_array($path, $configuredSets, true)) {
        $configuredSets[] = $path;
    }
}

$rectorConfig = RectorConfig::configure();

if (ConfigValidation::isNonEmptyString($config->cacheDir)) {
    $rectorConfig = $rectorConfig->withCache(
        cacheDirectory: $config->cacheDir,
        cacheClass: FileCacheStorage::class
    );
}

$rectorConfig = $rectorConfig
    ->withRootFiles()
    ->withIndent(' ', 4)
    ->withImportNames(
        importNames: $config->importNames->importNames,
        importDocBlockNames: $config->importNames->importDocBlockNames,
        importShortClasses: $config->importNames->importShortClasses,
        removeUnusedImports: $config->importNames->removeUnusedImports,
    )->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        naming: true,
        instanceOf: true,
        earlyReturn: true,
        carbon: false, // Breaks JSON serialization (Carbon vs DateTime output differs)
        rectorPreset: true,
        phpunitCodeQuality: true,
    )
    ->withComposerBased(
        doctrine: true,
        phpunit: true,
        symfony: $config->isSymfonyProject(),
        laravel: $config->isLaravelProject(),
    )
    ->withAttributesSets(
        symfony: $config->isSymfonyProject(),
        doctrine: true,
        mongoDb: true,
        gedmo: true,
        phpunit: true,
    )
    ->withSets($configuredSets)
    ->withPhpVersion($config->phpVersion->getRectorPhpVersion())
    ->withPaths($config->paths)
    ->withSkip(
        array_merge(
            $config->skipDirs,
            $config->skipFiles,
            [
                NoSetupWithParentCallOverrideRector::class, // Conflicts with AddOverrideAttributeToOverriddenMethodsRector — phantom changes on every run
                SimplifyBoolIdenticalTrueRector::class, // Breaks Laravel route closure detection
                IsCountableRector::class, // Produces incorrect results with custom collection types
                RestoreDefaultNullToNullableTypePropertyRector::class, // Conflicts with DTO nullable property patterns
                RemoveExtraParametersRector::class, // Removes arguments from dump() and similar variadic functions
                ClosureToArrowFunctionRector::class, // Breaks Laravel route closure definitions
                SeparateMultiUseImportsRector::class, // Breaks classes using multiple Traits on one line
                LocallyCalledStaticMethodToNonStaticRector::class, // Overly aggressive static-to-instance conversion
                PreferPHPUnitThisCallRector::class, // Conflicts with PHPStan static analysis expectations
                RenamePropertyToMatchTypeRector::class, // Breaks Doctrine entity property naming conventions
                RenameVariableToMatchMethodCallReturnTypeRector::class, // Produces overly verbose variable names
                RenameForeachValueVariableToMatchMethodCallReturnTypeRector::class, // Produces overly verbose variable names
                RenameForeachValueVariableToMatchExprVariableRector::class, // Breaks PHPUnit test variable naming
                TypedPropertyFromCreateMockAssignRector::class, // Breaks PHPUnit mock property type declarations
                RenameVariableToMatchNewTypeRector::class, // Breaks PHPUnit test variable naming

                // Under review — may be re-enabled in future versions
                AddMethodCallBasedStrictParamTypeRector::class, // Breaks classes using multiple Traits
                FlipTypeControlToUseExclusiveTypeRector::class, // Produces less readable conditionals
                IssetOnPropertyObjectToPropertyExistsRector::class, // Breaks nullable property checks
                RenameParamToMatchTypeRector::class, // Produces overly verbose parameter names
                PostIncDecToPreIncDecRector::class, // Conflicts with post-increment style convention
                AddEscapeArgumentRector::class, // Breaks functions with variadic arguments and no defined parameters
                PropertyCreateMockToCreateStubRector::class, // Expectations configured on test doubles that are created as test stubs are no longer verified since PHPUnit 10
                CreateStubOverCreateMockArgRector::class, // Expectations configured on test doubles that are created as test stubs are no longer verified since PHPUnit 10
                ExpressionCreateMockToCreateStubRector::class, // Expectations configured on test doubles that are created as test stubs are no longer verified since PHPUnit 10
                BareCreateMockAssignToDirectUseRector::class, // Expectations configured on test doubles that are created as test stubs are no longer verified since PHPUnit 10

                // Known upstream issues — waiting for Rector fixes
                MakeInheritedMethodVisibilitySameAsParentRector::class,
                RemoveParentCallWithoutParentRector::class,
            ],
        )
    )
    ->withFileExtensions(\Linters\DTO\RectorConfig::FILE_EXTENSIONS);

if ($config->unsafe->treatClassesAsFinal) {
    $rectorConfig = $rectorConfig->withTreatClassesAsFinal();
}

if ($config->parallel?->enabled) {
    // https://getrector.com/documentation/troubleshooting-parallel
    $rectorConfig = $rectorConfig->withParallel(
        $config->parallel->timeout ?? 120,
        $config->parallel->maxProcesses ?? 16,
        $config->parallel->filesPerProcess ?? 15,
    );
}

if (ConfigValidation::isNonEmptyString($config->memoryLimit)) {
    $rectorConfig = $rectorConfig->withMemoryLimit($config->memoryLimit);
}

return $rectorConfig;
