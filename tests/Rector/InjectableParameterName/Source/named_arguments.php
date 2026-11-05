<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\Rector\InjectableParameterName\Source;

use DateTimeImmutable;

function unrelatedDateConstructor(): DateTimeImmutable
{
    return new DateTimeImmutable(datetime: 'now');
}
