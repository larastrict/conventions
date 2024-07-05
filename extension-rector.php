<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector;
use Rector\Config\RectorConfig;
use Rector\Php70\Rector\StaticCall\StaticCallOnNonStaticToInstanceCallRector;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;
use StrictPhp\Conventions\ExtensionFiles;

$routes = '/*/routes/*';
$configs = '*/config/*';

return RectorConfig::configure()
    ->withSkip([
        '*.blade.php',
        CallableThisArrayToAnonymousFunctionRector::class => [$routes],
        FirstClassCallableRector::class => [$routes, $configs],
        StaticCallOnNonStaticToInstanceCallRector::class =>  [$routes, $configs],
    ])
    ->withSets([ExtensionFiles::Rector]);
