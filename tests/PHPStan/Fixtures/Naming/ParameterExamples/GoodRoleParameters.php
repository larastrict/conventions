<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\ParameterExamples;

use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Actions\SaveAction;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Contracts\CreateOrderQueryContract;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Interfaces\PaymentServiceInterface;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Interfaces\PaymentServiceInterface as PaymentInterface;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Queries\Builders\AccountBuilder;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Queries\FindQuery;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Services\SaveService;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Transformers\RequestToAccountEntity;

final class GoodRoleParameters
{
    public function __construct(
        private CreateOrderQueryContract $createOrderQuery,
        private PaymentServiceInterface $paymentService,
        private PaymentInterface $previousPayment,
        private SaveService $saveService,
        private SaveAction $saveAction,
        private RequestToAccountEntity $requestToAccountEntity,
        private AccountBuilder $accountBuilder,
        private ?FindQuery $findQuery,
    ) {
    }
}
