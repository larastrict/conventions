<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Naming\ParameterExamples;

use DateTimeImmutable;
use Lcobucci\JWT\Builder as BuilderInterface;
use Psr\Log\LoggerInterface;

final class ExternalParameters
{
    public function __construct(
        private BuilderInterface $builder,
        private LoggerInterface $logger,
        private DateTimeImmutable $createdAt,
    ) {
    }
}
