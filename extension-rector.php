<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use StrictPhp\Conventions\ExtensionFiles;

$routes = '/*/routes/*';
$configs = '*/config/*';

return RectorConfig::configure()
    ->withSkip([
        '*.blade.php',
    ])
    ->withSets([ExtensionFiles::Rector]);
