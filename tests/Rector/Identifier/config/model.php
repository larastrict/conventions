<?php declare(strict_types=1);

use LaraStrict\Conventions\Rector\Identifier\ModelIdentifierConstantsRector;
use LaraStrict\Conventions\Rector\Identifier\QueryAliasConstantsRector;
use LaraStrict\Conventions\Rector\Identifier\RelationIdentifierConstantsRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()->withRules([
    ModelIdentifierConstantsRector::class,
    QueryAliasConstantsRector::class,
    RelationIdentifierConstantsRector::class,
]);
