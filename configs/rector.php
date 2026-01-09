<?php

declare(strict_types=1);

use Linters\Rector\Set\AppRectorSetList;
use Linters\Utils\ConfigurationLoader;
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
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Exception\Configuration\InvalidConfigurationException;
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
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\Class_\TypedPropertyFromCreateMockAssignRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddMethodCallBasedStrictParamTypeRector;
use Rector\ValueObject\PhpVersion;

$composerLoader = new ConfigurationLoader();

$frameworksValue = $composerLoader->get('rector.frameworks', []);
$frameworks = [];
$isSymfonyEnabled = false;
$isLaravelEnabled = false;

if (is_array($frameworksValue)) {
    if (array_is_list($frameworksValue)) {
        $frameworks = $frameworksValue;
    } else {
        foreach ($frameworksValue as $name => $enabled) {
            if ($enabled) {
                $frameworks[] = (string)$name;
            }
        }
    }
} elseif (is_string($frameworksValue) && $frameworksValue !== '') {
    $frameworks[] = $frameworksValue;
}

$frameworks = array_map(
    static fn(string $framework): string => strtolower($framework),
    $frameworks
);

$frameworkSets = [];

if (in_array('laravel', $frameworks, true)) {
    $frameworkSets[] = AppRectorSetList::LARAVEL;
    $isLaravelEnabled = true;
}

if (in_array('symfony', $frameworks, true)) {
    $frameworkSets[] = AppRectorSetList::SYMFONY;
    $isSymfonyEnabled = true;
}

$baseSets = [
    AppRectorSetList::APP_RULES,
    AppRectorSetList::DOCTRINE,

    PHPUnitSetList::PHPUNIT_90,
    PHPUnitSetList::PHPUNIT_100,
    PHPUnitSetList::PHPUNIT_110,
    PHPUnitSetList::PHPUNIT_CODE_QUALITY,
    PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,

    SetList::CODE_QUALITY,
    SetList::CODING_STYLE,
    SetList::TYPE_DECLARATION,
    SetList::EARLY_RETURN,

    DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
    DoctrineSetList::GEDMO_ANNOTATIONS_TO_ATTRIBUTES,
    DoctrineSetList::MONGODB__ANNOTATIONS_TO_ATTRIBUTES,
    DoctrineSetList::DOCTRINE_CODE_QUALITY,

    LevelSetList::UP_TO_PHP_82,
];

$configuredSets = array_merge($baseSets, $frameworkSets);

/**
 * @throws InvalidConfigurationException
 */
$rectorConfig = RectorConfig::configure();

$cacheDir = $composerLoader->get('rector.cache_dir');
if (is_string($cacheDir) && $cacheDir !== '') {
    $rectorConfig = $rectorConfig->withCache(
        cacheDirectory: $cacheDir,
        cacheClass: FileCacheStorage::class
    );
}

return $rectorConfig
    ->withRootFiles()
    // https://getrector.com/documentation/troubleshooting-parallel
    ->withParallel(360, 2, 40)
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
        symfony: $isSymfonyEnabled,
        laravel: $isLaravelEnabled,
    )
    ->withAttributesSets(
        doctrine: true,
        mongoDb: true,
        gedmo: true,
        phpunit: true,
    )
    ->withSets($configuredSets)
    ->withPhpVersion(PhpVersion::PHP_82)
    ->withPaths($composerLoader->getAbsolutePaths('rector.paths'))
    ->withSkip(
        array_merge(
            $composerLoader->getAbsolutePaths('rector.skip'),
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
    ->withFileExtensions(['php']);
