<?php declare(strict_types=1);

namespace LaraStrict\Conventions\Architecture;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\InterpolatedStringPart;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\Node\Scalar\String_;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * Shared policy for model attribute and relationship identifiers.
 */
final class IdentifierPolicy
{
    /**
     * @var list<string>
     */
    public const array RELATION_CALLS = ['with', 'without', 'wherehas', 'orwherehas', 'wheredoesnthave', 'load', 'loadmissing', 'setrelation', 'unsetrelation', 'relationloaded'];

    /**
     * @var list<string>
     */
    public const array ATTRIBUTE_CALLS = ['where', 'orwhere', 'wherenull', 'wherenotnull', 'wherein', 'wherenotin', 'orderby', 'orderbydesc', 'groupby', 'select', 'addselect', 'pluck', 'value', 'increment', 'decrement'];

    /**
     * @var list<string>
     */
    public const array MODEL_DEFINITION_PROPERTIES = ['casts', 'fillable', 'guarded', 'hidden', 'visible'];

    /**
     * @var list<string>
     */
    public const array MUTABLE_ATTRIBUTE_DEFINITION_PROPERTIES = self::MODEL_DEFINITION_PROPERTIES;

    /**
     * @var list<string>
     */
    private const array TIMESTAMP_CONSTANTS = ['CREATED_AT', 'UPDATED_AT', 'DELETED_AT'];

    public static function callPrefix(string $method): ?string
    {
        $method = strtolower($method);

        return in_array($method, self::RELATION_CALLS, true)
            ? 'Relation'
            : (in_array($method, self::ATTRIBUTE_CALLS, true) ? 'Attribute' : null);
    }

    /**
     * Return a simple trailing alias from a direct selectRaw()->value() chain.
     * Complex SQL expressions are deliberately left unclassified.
     */
    public static function selectedRawAlias(MethodCall $valueCall): ?string
    {
        $details = self::selectedRawAliasDetails($valueCall);

        if ($details === null) {
            return null;
        }

        $value = $valueCall->getArgs()[0]->value ?? null;

        return $value instanceof String_ && $value->value === $details['alias'] ? $value->value : null;
    }

    /**
     * @return array{alias: string, sql: String_|InterpolatedString, prefix: string, suffix: string, partIndex: ?int}|null
     */
    public static function selectedRawAliasDetails(MethodCall $valueCall): ?array
    {
        if (! $valueCall->name instanceof Identifier || strtolower($valueCall->name->toString()) !== 'value' || ! $valueCall->var instanceof MethodCall || ! $valueCall->var->name instanceof Identifier || strtolower($valueCall->var->name->toString()) !== 'selectraw') {
            return null;
        }

        if ($valueCall->isFirstClassCallable() || $valueCall->var->isFirstClassCallable()
            || array_filter([...$valueCall->getArgs(), ...$valueCall->var->getArgs()], static fn (Arg $argument): bool => $argument->name instanceof Identifier) !== []) {
            return null;
        }

        $sql = $valueCall->var->getArgs()[0]->value ?? null;

        if ($sql instanceof String_) {
            return self::rawAliasFromText($sql->value, $sql, null);
        }

        if (! $sql instanceof InterpolatedString || $sql->parts === []) {
            return null;
        }

        $index = array_key_last($sql->parts);
        $part = $sql->parts[$index];

        if (! $part instanceof InterpolatedStringPart) {
            return null;
        }

        return self::rawAliasFromText($part->value, $sql, $index);
    }

    /**
     * @return array{alias: string, sql: String_|InterpolatedString, prefix: string, suffix: string, partIndex: ?int}|null
     */
    private static function rawAliasFromText(string $text, String_|InterpolatedString $sql, ?int $partIndex): ?array
    {
        if (preg_match('/(?:--|#|\\/\\*)/', $text) === 1) {
            return null;
        }

        if (preg_match('/\\bas\\s+([A-Za-z_][A-Za-z0-9_]*)(\\s*)$/iD', $text, $matches, PREG_OFFSET_CAPTURE) !== 1) {
            return null;
        }

        return [
            'alias' => $matches[1][0],
            'sql' => $sql,
            'prefix' => substr($text, 0, $matches[1][1]),
            'suffix' => $matches[2][0],
            'partIndex' => $partIndex,
        ];
    }

    public static function isIdentifier(string $value): bool
    {
        return preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $value) === 1;
    }

    public static function isSnakeCase(string $name): bool
    {
        return preg_match('/^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$/D', $name) === 1;
    }

    public static function constantName(string $prefix, string $value): string
    {
        return $prefix . Str::studly($value);
    }

    /**
     * @param array<string, mixed> $constants
     */
    public static function matchingConstantName(array $constants, string $value, string $prefix): ?string
    {
        foreach ($constants as $name => $constantValue) {
            if ($constantValue === $value && self::acceptsConstantName($name, $prefix)) {
                return $name;
            }
        }

        return null;
    }

    public static function acceptsConstantName(string $name, string $prefix): bool
    {
        return str_starts_with($name, $prefix)
            || ($prefix === 'Attribute' && in_array($name, self::TIMESTAMP_CONSTANTS, true));
    }

    public static function model(ReflectionProvider $reflectionProvider, string $className): ?ClassReflection
    {
        if ($reflectionProvider->hasClass($className) === false) {
            return null;
        }

        $class = $reflectionProvider->getClass($className);

        return $class->isSubclassOfClass($reflectionProvider->getClass(Model::class)) ? $class : null;
    }

    public static function returnsRelation(Type $type): bool
    {
        return ! $type instanceof NeverType && (new ObjectType(Relation::class))->isSuperTypeOf($type)->yes();
    }

    public static function isFormRequest(Type $type): bool
    {
        return (new ObjectType(FormRequest::class))->isSuperTypeOf($type)->yes();
    }

    public static function isWildcardRequestKey(string $value): bool
    {
        return preg_match('/^[.*]+$/D', $value) === 1;
    }

    public static function isSupportedRequestKey(string $value): bool
    {
        return preg_match('/^[A-Za-z][A-Za-z0-9_.-]*$/D', $value) === 1;
    }
}
