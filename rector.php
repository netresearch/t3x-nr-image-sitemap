<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\StaticCall\RemoveParentCallWithoutParentRector;
use Rector\Php73\Rector\ConstFetch\SensitiveConstantNameRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\FunctionLike\MixedTypeRector;
use Rector\Set\ValueObject\LevelSetList;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;
use Ssch\TYPO3Rector\Set\Typo3SetList;
use Ssch\TYPO3Rector\Rector\v11\v0\ExtbaseControllerActionsMustReturnResponseInterfaceRector;


return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/Classes',
        __DIR__ . '/Configuration',
        __DIR__ . '/Resources',
        'ext_*',
    ]);

    $rectorConfig->skip([
        'ext_emconf.php',
        'ext_*.sql',
    ]);


    // define sets of rules
       $rectorConfig->sets([
           LevelSetList::UP_TO_PHP_81,
           Typo3LevelSetList::UP_TO_TYPO3_11,

           Typo3SetList::UNDERSCORE_TO_NAMESPACE,
           Typo3SetList::DATABASE_TO_DBAL,
           Typo3SetList::EXTBASE_COMMAND_CONTROLLERS_TO_SYMFONY_COMMANDS,
           Typo3SetList::REGISTER_ICONS_TO_ICON,


       ]);
    $rectorConfig->skip([
        ClassPropertyAssignToConstructorPromotionRector::class,
        ExtbaseControllerActionsMustReturnResponseInterfaceRector::class,
        MixedTypeRector::class,
        SensitiveConstantNameRector::class,
        RemoveParentCallWithoutParentRector::class,
    ]);

};
