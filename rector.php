<?php

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\ValueObject\PhpVersion;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/public',
    ]);

    $rectorConfig->phpVersion(PhpVersion::PHP_81);

    // Define what rule sets will be applied
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81,  // Upgrade to PHP 8.1 features
        SetList::CODE_QUALITY,       // General code quality improvements
        SetList::CODING_STYLE,       // PSR-12 coding style enforcement
    ]);

    // Skip all code quality changes for test files
    $rectorConfig->skip([
        __DIR__ . '/tests',
    ]);

    $rectorConfig->cacheDirectory(__DIR__ . '/var/rector');
};
