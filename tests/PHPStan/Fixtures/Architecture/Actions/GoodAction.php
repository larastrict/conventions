<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan\Fixtures\Architecture\Actions;

final class GoodAction
{
    public function __construct(
        private string $value,
    )
    {
    }

    public function execute(): string
    {
        return $this->value;
    }
}
