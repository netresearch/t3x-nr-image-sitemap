<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/Classes',
        __DIR__ . '/Configuration',
    ]);

    // register a single rule
    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

    // define sets of rules
       $rectorConfig->sets([
           \Ssch\TYPO3Rector\Set\Typo3LevelSetList::UP_TO_TYPO3_11,
       ]);
      $rectorConfig->phpVersion(\Rector\Core\ValueObject\PhpVersion::PHP_81);
};
