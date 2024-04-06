<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use StrictPhp\Conventions\ExtensionFiles;

return RectorConfig::configure()
    ->withSets([ExtensionFiles::Rector]);
