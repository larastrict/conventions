<?php declare(strict_types=1);

use LaraStrict\Conventions\Rector\Identifier\RequestKeyConstantsRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()->withRules([RequestKeyConstantsRector::class]);
