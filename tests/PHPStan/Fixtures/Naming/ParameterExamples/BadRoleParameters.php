<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\ParameterExamples;

use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Actions\SaveAction;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Factories\PaymentFactory;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Interfaces\PaymentServiceInterface;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Managers\PaymentManager;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Queries\Builders\AccountBuilder;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Queries\FindQuery;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Services\SaveService;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Transformers\RequestToAccountEntity;

final class BadRoleParameters
{
    public function __construct(
        private PaymentServiceInterface $strategy,
        private SaveService $service,
        private SaveAction $action,
        private RequestToAccountEntity $converter,
        private AccountBuilder $builder,
        private FindQuery $query,
        private PaymentFactory $factory,
        private PaymentManager $manager,
    ) {
    }
}
