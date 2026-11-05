<?php declare(strict_types=1);

namespace LaraStrict\Conventions\PHPStan;

/**
 * A diagnostic and its unambiguous class-name or placement correction.
 */
final readonly class ClassConventionViolation
{
    public function __construct(
        public string $message,
        public string $identifier,
        public int $line,
        public ?string $expectedClass = null,
    ) {}
}
