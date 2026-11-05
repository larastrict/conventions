<?php declare(strict_types=1);

use LaraStrict\Conventions\Rector\ClassNamingRector;
use LaraStrict\Conventions\Rector\DependencyInjectionRector;
use LaraStrict\Conventions\Rector\Identifier\ModelIdentifierConstantsRector;
use LaraStrict\Conventions\Rector\Identifier\QueryAliasConstantsRector;
use LaraStrict\Conventions\Rector\Identifier\RelationIdentifierConstantsRector;
use LaraStrict\Conventions\Rector\Identifier\RequestKeyConstantsRector;
use LaraStrict\Conventions\Rector\InjectableParameterNameRector;
use Rector\Config\RectorConfig;
use StrictPhp\Conventions\ExtensionFiles;

$routes = '/*/routes/*';
$configs = '*/config/*';

return RectorConfig::configure()
    ->withSkip([
        '*.blade.php',
    ])
    ->withSets([ExtensionFiles::Rector])
    ->withRules([
        ClassNamingRector::class,
        RelationIdentifierConstantsRector::class,
        ModelIdentifierConstantsRector::class,
        QueryAliasConstantsRector::class,
        RequestKeyConstantsRector::class,
        DependencyInjectionRector::class,
        InjectableParameterNameRector::class,
    ]);
