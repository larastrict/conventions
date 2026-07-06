<?php

declare(strict_types=1);

use LaraStrict\Conventions\ExtensionFiles;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withRootFiles()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/fixtures', __DIR__ . '/tests'])
    ->withSets([ExtensionFiles::Rector]);
