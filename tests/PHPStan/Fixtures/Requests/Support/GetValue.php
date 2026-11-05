<?php declare(strict_types=1);

namespace Wrkflow\GetValue;

// Minimal typed-value API for rule tests; the package is optional in consumers.
class GetValue
{
    /**
     * @param string|list<string> $key
     * @param list<string> $rules
     */
    public function getRequiredString(string|array $key, array $rules = []): string
    {
        return '';
    }

    /**
     * @param string|list<string> $key
     */
    public function getRequiredObject(string $expectedClass, string|array $key, object $getValueTransformer): object
    {
        return new \stdClass();
    }
}
