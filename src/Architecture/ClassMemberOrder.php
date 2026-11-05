<?php declare(strict_types=1);

namespace LaraStrict\Conventions\Architecture;

final class ClassMemberOrder
{
    public const array Standard = ['use_trait', 'case', 'constant', 'property_static', 'property', 'construct', 'method'];
    public const array ExecuteFirst = ['use_trait', 'case', 'constant', 'property_static', 'property', 'construct', 'method:execute', 'method'];
}
