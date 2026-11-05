# LaraStrict conventions

## MUST USE

- Use `$larastrict` and the relevant `$larastrict-*` skills for implementation details. Laravel conventions extend StrictPHP.
- Inject collaborators and clocks through supported lifecycle methods.
- Keep persistence in Queries. Extract reused workflows into Actions; focused endpoint workflows may stay in Controllers.
- Use typed request and view data, and named constants for request and model keys.
- Use `RunInTransactionActionContract` for application transactions.

## DON'T USE

- Do not resolve injectable collaborators manually or construct dates statically where injection is supported.
- Do not use Queries to orchestrate business workflows.
- Do not use `toArray()` for transport between application layers; protocol serialization is allowed.
