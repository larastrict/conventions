<?php declare(strict_types=1);

namespace LaraStrict\ConventionsTests\PHPStan;

use LaraStrict\Conventions\PHPStan\ModelIdentifierRule;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Models\Bad\Invoice;
use LaraStrict\ConventionsTests\PHPStan\Fixtures\Models\Good\Order;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ModelIdentifierRule>
 */
final class ModelIdentifierRuleTest extends RuleTestCase
{
    private const string APPLICATION_NAMESPACE = 'LaraStrict\\ConventionsTests\\PHPStan\\Fixtures\\Models';

    private bool $enabled = true;

    protected function getRule(): Rule
    {
        return new ModelIdentifierRule(self::createReflectionProvider(), [self::APPLICATION_NAMESPACE], enabled: $this->enabled);
    }

    public function testDisabledRuleAllowsViolations(): void
    {
        $this->enabled = false;

        $this->analyse([__DIR__ . '/Fixtures/Models/Bad/IdentifierUsage.php'], []);
    }

    public function testModelConstantsTraitRelationsAndValueAccessAreAllowed(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Models/Support/Customer.php',
            __DIR__ . '/Fixtures/Models/Good/OrderCapability.php',
            __DIR__ . '/Fixtures/Models/Good/HasCustomerRelation.php',
            __DIR__ . '/Fixtures/Models/Good/Order.php',
            __DIR__ . '/Fixtures/Models/Good/UnrelatedRecord.php',
            __DIR__ . '/Fixtures/Models/Good/IdentifierUsage.php',
        ], []);
    }

    public function testModelDefinitionKeysAndTraitRelationshipsAreReported(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Models/Support/Customer.php',
            __DIR__ . '/Fixtures/Models/Bad/InvoiceCapability.php',
            __DIR__ . '/Fixtures/Models/Bad/HasMissingRelation.php',
            __DIR__ . '/Fixtures/Models/Bad/Invoice.php',
        ], [
            ['Define a Relation* constant for the invoices() relationship, on the Model or its capability interface.', 11],
            ['Use ' . Invoice::class . "::AttributeId instead of the literal 'id'.", 13],
            ['Use ' . Invoice::class . "::AttributeNumber instead of the literal 'number'.", 13],
            ['Use ' . Invoice::class . "::AttributeOwnerId instead of the literal 'owner_id'.", 14],
            ['Use ' . Invoice::class . "::AttributeSecret instead of the literal 'secret'.", 15],
            ['Use ' . Invoice::class . "::AttributeName instead of the literal 'name'.", 16],
            ['Use ' . Invoice::class . "::CREATED_AT instead of the literal 'created_at'.", 18],
            ['Define a Relation* constant for the customer() relationship, on the Model or its capability interface.', 24],
            ['Relationship method missingRelation() must use snake_case.', 32],
        ]);
    }

    public function testRelationMethodsUseSnakeCaseOnModelsAndTraitsEvenWithConstants(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Models/Bad/HasOrderItems.php',
            __DIR__ . '/Fixtures/Models/Bad/CamelRelations.php',
            __DIR__ . '/Fixtures/Models/Good/SnakeRelations.php',
        ], [
            ['Relationship method orderItems() must use snake_case.', 11],
            ['Relationship method customerAccount() must use snake_case.', 19],
        ]);
    }

    public function testEloquentIdentifierLiteralsAreReportedAndOtherExpressionsSkipped(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Models/Bad/IdentifierUsage.php'], [
            ['Use ' . Invoice::class . "::AttributeNumber instead of the literal 'number'.", 11],
            ['Use ' . Invoice::class . "::RelationCustomer instead of the literal 'customer'.", 12],
            ['Use ' . Invoice::class . "::AttributeOwnerId instead of the literal 'owner_id'.", 13],
            ['Use ' . Invoice::class . "::RelationMissingRelation instead of the literal 'missingRelation'.", 14],
            ['Use ' . Invoice::class . "::RelationLines instead of the literal 'lines'.", 15],
        ]);
    }

    public function testOnlyTheMatchingProjectedAliasIsExempt(): void
    {
        $message = 'Use ' . Order::class . "::AttributeStatus instead of the literal 'status'.";
        $this->analyse([__DIR__ . '/Fixtures/Models/Bad/ProjectedColumns.php'], [
            [$message, 12],
            [$message, 13],
        ]);
    }

    public function testCastAttributesAndDocumentedPropertiesDoNotDefineRelationships(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Models/Good/CastAttributes.php'], []);
    }
}
