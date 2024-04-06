<?php

declare(strict_types=1);

use LaraStrict\Conventions\ExtensionFiles;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withRootFiles()
    ->withPaths([__DIR__ . '/src'])
    ->withSets([ExtensionFiles::Rector]);
