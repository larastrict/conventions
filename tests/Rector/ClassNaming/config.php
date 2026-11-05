<?php declare(strict_types=1);

use LaraStrict\Conventions\Rector\ClassNamingRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()->withConfiguredRule(ClassNamingRector::class, [
    'renames' => [
        'App\Verification\Models\Scopes\WhereStateIn' => 'App\Verification\Models\Scopes\WhereStateInScope',
        'App\Billing\InvoiceEntityTransformer' => 'App\Billing\Transformers\InvoiceEntityTransformer',
    ],
])->withImportNames();
