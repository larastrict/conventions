<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Services;

use Carbon;
use Carbon\Factory;
use Carbon\Factory as Clock;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Actions\Workflow\Action;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Builders\Report\BuilderContract;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Queries\Catalog\Query;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Services\Payment\Factory as PaymentFactory;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Services\Payment\ServiceInterface;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Transformers\Output\Transformer;

final class GenericNamesService
{
    public function __construct(
        Factory $clock,
        Factory $carbonFactory,
        ?Factory $previousCarbonFactory,
        \Carbon\Factory $fullCarbonFactory,
        Carbon\Factory $namespacedCarbonFactory,
        Clock $namedClock,
        PaymentFactory $paymentFactory,
        ServiceInterface $service,
        BuilderContract $builder,
        Action $action,
        Query $catalogQuery,
        Transformer $outputTransformer,
        Factory $factory,
        Query $query,
        Transformer $transformer,
    ) {}
}
