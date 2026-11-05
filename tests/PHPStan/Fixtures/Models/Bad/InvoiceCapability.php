<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Models\Bad;

interface InvoiceCapability
{
    public const RelationLines = 'lines';
    public const RelationMissingRelation = 'missingRelation';
}
