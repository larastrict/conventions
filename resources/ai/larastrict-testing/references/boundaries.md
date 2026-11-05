# Choose the test boundary

Prefer a plain PHPUnit TestCase for isolated Actions, Services, Transformers, validation logic, and [contexts](../../larastrict-cache/references/contexts.md). Use the consumer's PHPUnit or Pest setup. If Laravel setup is needed, use its typed test-construction helper when available.

Direct database-query and seeder tests may boot Laravel and use a real database. Invoke the Query or seeder directly. Test controller behavior, API contracts, route access decisions, and user flows with the repository's Playwright or Cypress setup against the running application. Reuse authentication, data, and cleanup fixtures. Reserve Laravel HTTP tests for special complex business cases required by the project.

When moving coverage between layers, preserve observable behavior checks. Keep persistence state and query-count checks in direct Query tests. Run focused tests during development and the checks required by the applicable `AGENTS.md` and `CONTRIBUTING.md` before commit or release.
