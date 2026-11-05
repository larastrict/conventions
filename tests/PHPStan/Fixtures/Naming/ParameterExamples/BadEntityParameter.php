<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\ParameterExamples;

use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Entities\AccountEntity;

final class BadEntityParameter
{
    public function execute(AccountEntity $accountEntity): void
    {
        unset($accountEntity);
    }
}
