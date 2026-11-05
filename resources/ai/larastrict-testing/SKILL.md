---
name: larastrict-testing
description: Write or update Larastrict tests by choosing unit, direct database, or browser boundaries and reusing or generating compatible contract Assert and Expectation helpers.
---

# Larastrict testing

Read the target application's applicable `AGENTS.md` and `CONTRIBUTING.md`. Resolve installed-source links from that application when using local skill symlinks.

1. Choose the behavior and test boundary using [test boundaries](references/boundaries.md). Prefer isolated PHP tests; use direct database tests for persistence and browser tests for HTTP flows.
2. Find a compatible generated Assert in application tests or `LaraStrict\Testing`, including its Laravel interface helpers. Keep Laravel interfaces as the dependency boundary. Introduce an application Contract when its behavior needs a test boundary.
3. If a helper is missing or outdated, follow [expectation generation](references/expectations.md). Run the installed generator, inspect its output, and use its actual constructor and method API. Do not hand-write or edit generated helpers.
4. Configure expected inputs and results, inject the Assert, and use the expectation manager to detect unused calls. Assert observable behavior as well as collaborator calls.
5. Run focused tests and the checks required by the application instructions. Review generated changes before final validation.

Read only the reference needed for the task. For cache-specific keys and lifecycle behavior, also use [larastrict-cache](../larastrict-cache/SKILL.md).
