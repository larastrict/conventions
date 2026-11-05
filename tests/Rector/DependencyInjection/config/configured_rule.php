<?php declare(strict_types=1);

use LaraStrict\Conventions\Rector\DependencyInjectionRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withRules([DependencyInjectionRector::class])
    ->withBootstrapFiles([__DIR__ . '/bootstrap.php']);
