<?php

namespace App\Actions;

final class SaveAction {}

namespace App\Services;

use App\Actions\SaveAction;

trait UsesActionProperty
{
    public function runAction(): void
    {
        $this->action->execute();
    }
}

final class CompactService
{
    public function __construct(private SaveAction $action)
    {
        compact('action');
    }
}

final class TraitService
{
    use UsesActionProperty;

    public function __construct(private SaveAction $action) {}
}

final class DynamicPropertyService
{
    public function __construct(private SaveAction $action, string $property)
    {
        $this->{$property};
    }
}

class ParentService
{
    protected SaveAction $saveAction;
}

final class ChildService extends ParentService
{
    public function __construct(private SaveAction $action) {}
}

final class ReceiverService
{
    public function __construct(private SaveAction $action) {}
    public function run(self $other): void { $other->action->execute(); }
}

namespace App\Http\Controllers;

use App\Actions\SaveAction;

final class AnonymousController
{
    public function __invoke(SaveAction $action): object
    {
        return new class {
            public function run(SaveAction $action): void {}
        };
    }
}
