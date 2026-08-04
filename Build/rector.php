<?php

/*
 * This file is part of the package netresearch/nr-image-sitemap.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Privatization\Rector\Property\PrivatizeFinalClassPropertyRector;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;
use Ssch\TYPO3Rector\Set\Typo3SetList;

$configure = require_once __DIR__ . '/../.build/vendor/netresearch/typo3-ci-workflows/config/rector/rector.php';

return static function (RectorConfig $rectorConfig) use ($configure): void {
    // Shared org base config: code-quality sets, rule skips, phpstan-rector.neon
    $configure($rectorConfig, __DIR__ . '/..');

    // paths() replaces the shared list — re-declared to keep Tests/ in scope,
    // which the shared $projectRoot default leaves out.
    $rectorConfig->paths(array_merge(
        [
            __DIR__ . '/../Classes',
            __DIR__ . '/../Configuration',
            __DIR__ . '/../Resources',
            __DIR__ . '/../Tests',
        ],
        glob(__DIR__ . '/../ext_*.php') ?: [],
    ));

    $rectorConfig->sets([
        Typo3SetList::CODE_QUALITY,
        Typo3SetList::GENERAL,
        Typo3LevelSetList::UP_TO_TYPO3_14,
    ]);

    $rectorConfig->skip([
        // ImageFileReference is final, but its mapped properties MUST stay
        // `protected`: the Extbase DataMapper assigns them through
        // AbstractDomainObject::_setProperty(), which does `$this->{$name} = $value`
        // from the PARENT class scope. A property declared `private` in the child is
        // inaccessible there, so hydration dies with
        // "Error: Cannot access private property ImageFileReference::$title".
        PrivatizeFinalClassPropertyRector::class,
    ]);
};
