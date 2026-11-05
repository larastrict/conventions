<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Services;

use DateTimeImmutable;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Entities\AccountEntity;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Factories\InputModelFactory;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Models\Customer;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\Queries\Builders\RuntimeBuilder;
use Lcobucci\JWT\Builder as BuilderInterface;
use Psr\Log\LoggerInterface;
use Wrkflow\GetValue\GetValue;

final class ExternalInputService
{
    public function __construct(
        private BuilderInterface $builder,
        private LoggerInterface $logger,
        private DateTimeImmutable $createdAt,
        private AccountEntity $input,
        private Customer $record,
        private GetValue $data,
        private InputModelFactory $fixture,
        private RuntimeBuilder $query,
    ) {
    }
}
