<?php

namespace App\Actions;

final class SaveAction {}

namespace App\Services;

use App\Actions\SaveAction;

final class HandleService
{
    public function __construct(private readonly SaveAction $action)
    {
        $this->run($this->action);
    }

    private function run(SaveAction $action): void {}
}

-----
<?php

namespace App\Actions;

final class SaveAction {}

namespace App\Services;

use App\Actions\SaveAction;

final class HandleService
{
    public function __construct(private readonly SaveAction $saveAction)
    {
        $this->run($this->saveAction);
    }

    private function run(SaveAction $action): void {}
}

