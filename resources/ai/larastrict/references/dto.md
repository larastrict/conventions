# DTO (Entities and conversion)

Choose the Entity style from the data's lifecycle:

| Style | Declaration and name | Use |
| --- | --- | --- |
| Immutable Entity | `final readonly class OrderEntity` | Input payloads, stable results, and data passed between layers when mutation is not part of the workflow |
| Mutable State Entity | `final class ProcessOrderStateEntity` | Process state that changes deliberately as the workflow advances |

Use immutable Entities by default. Use a `*StateEntity` when mutation is meaningful, local to the workflow, and clearer than rebuilding the object after each step. Keep typed properties and make state transitions explicit in the owning Action or Service. Keep fixed inputs readonly where useful. Both styles normally belong in `Entities`. An endpoint-specific DTO may stay beside its Controller and Resource in `Http/{area}/{endpoint}`; see [HTTP layouts](http-and-views.md#http-layouts). Parameters omit only the final `Entity`, for example `ProcessOrderStateEntity $processOrderState`.

Parse external payloads at the boundary. A `Transformer` changes representation, such as payload to Entity, Model to Entity, or workflow state to an immutable result. Use one for substantial or reused conversion and inject it. Keep business state transitions in Actions or Services and persistence in Queries. A Transformer must not load missing relations to complete its mapping; prepare the source data in a Query.

Keep converters in `Transformers`. Name them with a `Transformer` suffix or as `SourceToTarget`, such as `RequestToOrderEntity` or `OrderEntityToArray`. In a conversion name, `Entity` describes the source or target; it does not make the converter a DTO or require the `Entities` namespace.

## Read the implementation

For typed payload conversion, read the installed [GetValue](../../../../vendor/wrkflow/php-get-typed-value/src/GetValue.php) and [GetValueTransformerContract](../../../../vendor/wrkflow/php-get-typed-value/src/Contracts/GetValueTransformerContract.php). Use the contract's installed signature and the getter's actual argument order. Source links resolve from the installed application skill.
