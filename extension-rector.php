<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php70\Rector\StaticCall\StaticCallOnNonStaticToInstanceCallRector;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;
use StrictPhp\Conventions\ExtensionFiles;

$routes = '/*/routes/*';
$configs = '*/config/*';

return RectorConfig::configure()
    ->withSkip([
        '*.blade.php',
        FirstClassCallableRector::class => [$routes, $configs],
        StaticCallOnNonStaticToInstanceCallRector::class =>  [$routes, $configs],
    ])
    ->withSets([ExtensionFiles::Rector]);
