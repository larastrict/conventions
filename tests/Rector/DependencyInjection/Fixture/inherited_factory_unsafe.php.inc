<?php

namespace App\Billing\Queries;

abstract class PrivateClockQuery
{
    public function __construct(private \Carbon\FactoryImmutable $carbonFactory) {}
}
final class PrivateChildQuery extends PrivateClockQuery
{
    public function execute(): mixed { return now(); }
}
abstract class NullableClockQuery
{
    public function __construct(protected ?\Carbon\FactoryImmutable $carbonFactory) {}
}
final class NullableChildQuery extends NullableClockQuery
{
    public function execute(): mixed { return now(); }
}
abstract class UninitializedClockQuery
{
    protected \Carbon\FactoryImmutable $carbonFactory;
    public function __construct() {}
}
final class UninitializedChildQuery extends UninitializedClockQuery
{
    public function execute(): mixed { return now(); }
}
abstract class PromotedClockQuery
{
    public function __construct(protected \Carbon\FactoryImmutable $carbonFactory) {}
}
abstract class OverridingClockQuery extends PromotedClockQuery
{
    public function __construct() {}
}
final class OverridingChildQuery extends OverridingClockQuery
{
    public function execute(): mixed { return now(); }
}
