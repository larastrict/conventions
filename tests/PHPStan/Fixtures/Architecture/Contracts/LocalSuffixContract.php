<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Architecture\Contracts;

interface LocalSuffixContract
{
    public function getFileSuffix(): string;
}
