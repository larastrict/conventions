<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\ParameterExamples;

use Carbon\Factory as CarbonFactory;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Contracts\CreateOrderQueryContract;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Entities\AccountEntity;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Entities\AccountEntity as ShippingEntity;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Models\Entity;

final class GoodParameters
{
    public function __construct(
        private CreateOrderQueryContract $createOrderQuery,
        private ?Entity $entity,
        Entity|null $optionalEntity,
        private CarbonFactory $carbonFactory,
        Entity $previousEntity,
        Entity $updatedEntity,
    ) {
        unset($optionalEntity, $previousEntity, $updatedEntity);
    }

    public function create(\Carbon\Factory $carbonFactory): void
    {
        unset($carbonFactory);
    }

    /**
     * @return array{CreateOrderQueryContract, Entity|null, CarbonFactory}
     */
    public function values(): array
    {
        return [$this->createOrderQuery, $this->entity, $this->carbonFactory];
    }

    public function compare(self $previousGoodParameters): bool
    {
        return $this === $previousGoodParameters;
    }

    public function entities(AccountEntity $account, ?AccountEntity $previousAccount, ShippingEntity $shipping): void
    {
        unset($account, $previousAccount, $shipping);
    }
}
