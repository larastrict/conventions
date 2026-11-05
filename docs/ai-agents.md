# AI agent setup

[Back to README](../README.md)

The shared StrictPHP sync command installs the StrictPHP skill, this package’s LaraStrict skills, skills from other installed packages, and skills registered by the root project. Register package skills under `extra.strictphp/conventions.skills` with general skill names. This package also registers `resources/ai/AGENTS.md` under `extra["strictphp/conventions"].instructions`; sync includes its short MUST USE and DON'T USE rules in the generated block. Skills remain separate from the collected rules; no skill catalog is added to `AGENTS.md`. See the [StrictPHP AI agent setup](https://github.com/strictphp/conventions/blob/main/docs/ai-agents.md) for the Composer format, generated files, ownership manifest and CI check.

## LaraStrict setup

Require both convention packages when the project uses LaraStrict:

```bash
composer require --dev strictphp/conventions larastrict/conventions
```

Register the shared sync command in the root project’s `composer.json`. Keep any existing install and update hooks. The hook skips sync when the development-only binary is absent, such as an install with `--no-dev`. The explicit check fails when the binary is absent.

```json
{
    "scripts": {
        "strictphp:sync-ai": "@php -r \"if (is_file('vendor/strictphp/conventions/bin/strictphp-sync-ai')) { require 'vendor/strictphp/conventions/bin/strictphp-sync-ai'; }\"",
        "strictphp:check-ai": "@php vendor/strictphp/conventions/bin/strictphp-sync-ai --check",
        "post-install-cmd": ["@strictphp:sync-ai"],
        "post-update-cmd": ["@strictphp:sync-ai"]
    }
}
```

Run `composer strictphp:sync-ai`, then commit the generated root `AGENTS.md` block, `.agents/skills/`, and `.agents/.strictphp-manifest.json`. Keep application-specific guidance outside the generated block. Check generated files with `composer strictphp:check-ai`.

For a local checkout, run `strictphp-sync-ai` with `--project /path/to/application`; add `--check` to check without writing.

## LaraStrict references

The most important Laravel rules live in `resources/ai/AGENTS.md` and are collected in the application's root `AGENTS.md`. For implementation details, use `$larastrict` and the relevant `$larastrict-*` skills. The `larastrict` skill reads the shared StrictPHP skill, the application rules, and its implementation conventions. It then reads only the focused reference required for the task. Source files are in [`resources/ai/larastrict/`](../resources/ai/larastrict/). Use the migration, caching, and testing skills for those workflows.

For PHPStan, ECS, Rector, and Blade checks, see [tool setup](setup.md), [PHPStan rules](phpstan.md), and [Rector fixes](rector.md).

## Blade checks

PHPStan checks Blade files in the configured analysis paths. Include the
application's existing view directories and exclude compiled views and vendor
templates. `ViewTemplateRule` compiles templates in memory and checks typed data
usage; it does not require a separate compiled-view cache.

Normal views document `$data` with an Entity PHPDoc. Components declare `@props`
before that PHPDoc. See [HTTP and view conventions](../resources/ai/larastrict/references/http-and-views.md#blade-data)
for examples and [PHPStan views](phpstan.md#views) for analysis configuration.
