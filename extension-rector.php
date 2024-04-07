<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector;
use Rector\Config\RectorConfig;
use Rector\Php70\Rector\StaticCall\StaticCallOnNonStaticToInstanceCallRector;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;
use StrictPhp\Conventions\ExtensionFiles;

$routes = '/*/routes/*';

return RectorConfig::configure()
    ->withSkip([
        '*.blade.php',
        FirstClassCallableRector::class => [$routes],
        StaticCallOnNonStaticToInstanceCallRector::class =>  [$routes],
        CallableThisArrayToAnonymousFunctionRector::class => [$routes],
    ])
    ->withSets([ExtensionFiles::Rector]);
