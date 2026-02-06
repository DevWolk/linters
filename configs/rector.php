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
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector;
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
    ->withImportNames(importDocBlockNames: false, importShortClasses: false)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        naming: true,
        instanceOf: true,
        earlyReturn: true,
        carbon: true,
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
                SimplifyBoolIdenticalTrueRector::class, // it's breaks the Routers
                IsCountableRector::class, // this rule does not fit, a lot of where it goes wrong
                RestoreDefaultNullToNullableTypePropertyRector::class, // don't work with DTO nullable parameter
                RemoveExtraParametersRector::class, // catting an argument in dump() function
                ClosureToArrowFunctionRector::class, // it's breaks the Routers
                SeparateMultiUseImportsRector::class, // it's breaks the using multiple Traits
                LocallyCalledStaticMethodToNonStaticRector::class,
                PreferPHPUnitThisCallRector::class, // it's breaks with phpstan
                RenamePropertyToMatchTypeRector::class, // it's breaks the Entity
                RenameVariableToMatchMethodCallReturnTypeRector::class, // it's redundant
                RenameForeachValueVariableToMatchMethodCallReturnTypeRector::class, // it's redundant
                RenameForeachValueVariableToMatchExprVariableRector::class, // it's breaks the unit tests
                TypedPropertyFromCreateMockAssignRector::class, // it's breaks the unit tests
                RenameVariableToMatchNewTypeRector::class, // it's breaks the unit tests

                //        THINKING
                AddMethodCallBasedStrictParamTypeRector::class, // it's breaks the using multiple Traits
                FlipTypeControlToUseExclusiveTypeRector::class,
                IssetOnPropertyObjectToPropertyExistsRector::class,
                RenameParamToMatchTypeRector::class,
                PostIncDecToPreIncDecRector::class,

                //        WAITING FIX
                MakeInheritedMethodVisibilitySameAsParentRector::class,
                RemoveParentCallWithoutParentRector::class,
            ],
        )
    )
    ->withFileExtensions(\Linters\DTO\RectorConfig::FILE_EXTENSIONS);

if ($config->parallel?->enabled) {
    // https://getrector.com/documentation/troubleshooting-parallel
    $rectorConfig = $rectorConfig->withParallel(
        $config->parallel->timeout,
        $config->parallel->maxProcesses,
        $config->parallel->filesPerProcess,
    );
}

if (ConfigValidation::isNonEmptyString($config->memoryLimit)) {
    $rectorConfig = $rectorConfig->withMemoryLimit($config->memoryLimit);
}

return $rectorConfig;
