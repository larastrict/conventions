# LaraStrict conventions

![LaraStrict banner](.github/banner.webp)

Shared code rules and tools for Laravel projects. Keep code consistent, find errors,
and give AI agents the same project guidance.

Built on [StrictPHP conventions](https://github.com/strictphp/conventions), with
PHPStan, Larastan, Easy Coding Standard, Rector, and PHPUnit.

## Install

Requires PHP 8.3 or later and Composer. Only the latest major version is maintained.

```bash
composer require --dev larastrict/conventions
```

Allow `phpstan/extension-installer` when Composer asks. It loads the PHPStan rules.
All LaraStrict rules are enabled by default, including container checks.
Boot the application and its service providers before running PHPStan.

Ask your coding agent to summarize the proposed changes and validation plan
before making changes. Use this prompt:

```text
Set up LaraStrict Conventions in this Laravel project.

Fetch and read:
https://raw.githubusercontent.com/larastrict/conventions/main/doc/setup-guide.md

Read the project's existing agent and contribution instructions first.
Inspect its PHP and Laravel versions, dependencies, and tool configuration.

Before running setup commands or changing files, summarize the proposed
installation, including dependency, configuration, script, and AI file changes,
and the validation plan.
Ask for permission to proceed and wait for the user's answer.
After approval, follow the guide.

Set up the development dependencies, Rector, ECS, PHPStan with Larastan,
Composer scripts, and the shared StrictPHP AI instruction sync.

Merge existing configuration and Composer hooks. Preserve project-specific
settings and the existing Laravel or Pest test runner.

Use the recommended Composer script names and execution order from the guide:
Rector → ECS → PHPStan → tests.

Validate setup with the guide's read-only checks and the existing test runner.
Resolve setup problems and report existing code violations or blocked checks.
Do not disable rules or generate baselines just to make the checks pass.

Summarize the changes and validation results. Then ask whether the user wants
an upgrade session using $larastrict to bring existing code into compliance.
Wait for approval before applying code transformations or fixing existing code.
If approved, agree on the scope and run Rector → ECS → PHPStan → tests.
```

You can also follow the complete [setup guide](doc/setup-guide.md) to configure
PHPStan, ECS, Rector, Composer scripts, and AI instructions.
Use Rector for class and reference changes. Move renamed files to their PSR-4
paths after Rector runs. See [Rector fixes](docs/rector.md) for the workflow.

## Review or upgrade existing code

After AI sync, the agent can discover the installed skills from their descriptions
and select them for normal implementation work. You do not need to name a skill
for each task. Use an explicit skill prompt when you want a convention review or
an upgrade session for existing code.

To adopt the conventions in an existing codebase:

```text
Use $larastrict to bring this project's existing code into compliance
with the installed conventions.

Inspect the codebase and apply the relevant skills and references.
Preserve application behavior and project-specific instructions.

Run Rector first, then ECS, then PHPStan, with tests last.
Fix reported violations without disabling rules or adding baselines.
Report changes and any remaining failures.
```

For a review without code changes:

```text
Use $larastrict to review app/Orders and its tests against the installed conventions.
Report findings with file references and suggested fixes. Do not change files.
```

To apply the fixes, request an upgrade session for that scope.

The Larastrict skill reads the shared StrictPHP skill first and adds Laravel
conventions. You do not need to name both skills in each prompt.

## Integrate your own package

Laravel packages use the shared `extra["strictphp/conventions"]` Composer key to
distribute AI instructions and skills. Use `instructions` for short package rules
and `skills` for implementation guidance. The same registration works in plain
PHP projects; a Laravel-specific skill can extend `$larastrict`.

Register the source files in the package, include them in its release, then run
`composer strictphp:sync-ai` in the consuming application. The shared sync collects
package rules into `AGENTS.md` and installs skills in `.agents/skills/`.
See the [StrictPHP package integration example](https://github.com/strictphp/conventions#integrate-your-own-package)
and [AI setup reference](docs/ai-agents.md).

## Change a rule

Each LaraStrict PHPStan rule has an `enabled` setting. For example, add this to
`phpstan.neon` to disable container checks:

```neon
services:
    larastrict.injectable:
        arguments:
            enabled: false
```

Set `enabled: true` to enable it again. See [PHPStan rules](docs/phpstan.md) for
all rule names and options.

## AI agent guidance

The package includes agent skills for Larastrict conventions, migrations,
caching, and testing. Sync them into your application to share the rules.
See [AI agent setup](docs/ai-agents.md) for Laravel setup. The shared skill sync is documented in [StrictPHP AI agent setup](https://github.com/strictphp/conventions/blob/main/docs/ai-agents.md).

## Documentation

- [Complete installation guide](doc/setup-guide.md)
- [Tool setup and commands](docs/setup.md)
- [PHPStan rules and settings](docs/phpstan.md)
- [Rector fixes and safety boundaries](docs/rector.md)
- [AI agent setup and Blade checks](docs/ai-agents.md)
- [Contributing](CONTRIBUTING.md)

## Supporters

Thanks to [Canvastera](https://canvastera.com),
[Azzurro Travel Agency](https://azzurro.cz), and
[Redtag Studio](https://redtag.studio).

Related projects: [LaraStrict](https://larastrict.com),
[StrictPHP](https://strictphp.com), and [WrkFlow](https://wrk-flow.com).

## License

[MIT](LICENSE.md).
