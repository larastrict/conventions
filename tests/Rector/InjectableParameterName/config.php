<?php declare(strict_types=1);

use LaraStrict\Conventions\Rector\InjectableParameterNameRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/fixtures'])
    ->withRules([InjectableParameterNameRector::class]);
