# Set up LaraStrict Conventions

[Back to README](../README.md)

Use this guide to install LaraStrict and its AI instructions in a Laravel
project. Read the project's applicable `AGENTS.md` and `CONTRIBUTING.md` files
first. Check its PHP and Laravel versions, Composer dependencies, source paths,
test runner, and existing quality tools. Preserve project rules and merge
configuration, scripts, and hooks instead of replacing them.

LaraStrict requires PHP 8.3 or later. Select package versions that support the
project's PHP and Laravel versions. Verify configuration APIs against the
installed package source and documentation. Report dependency conflicts or
missing features instead of disabling checks. Use versions that include the
shared `strictphp-sync-ai` command and LaraStrict package registrations.

## Approval before setup

For an AI-assisted installation, read this guide and inspect the project without
changing files or running setup commands. Summarize the proposed dependencies,
configuration, Composer scripts, and generated AI files. Ask for permission to
perform the installation and wait for an explicit answer before proceeding.
This approval covers setup and validation. Ask separately before an upgrade
session that changes existing application code.

## 1. Install the development dependencies

```bash
composer config allow-plugins.phpstan/extension-installer true
composer require --dev strictphp/conventions larastrict/conventions
```

Larastrict requires StrictPHP. Use `phpstan/extension-installer` for PHPStan
extension discovery. Do not also include `extension.neon` manually when the
plugin loads it. Preserve other Composer plugin settings and required
development tools. Do not use `--ignore-platform-reqs` to bypass requirements.

## 2. Configure Rector and ECS

Create or merge `rector.php` and `ecs.php` in the project root. The paths helper
returns `app`, `database`, `lang`, `config`, `routes`, and `tests`. Set
`laravel11Structure: true` when `bootstrap/app.php` and
`bootstrap/providers.php` exist. Include other maintained PHP files with the
tool's path configuration when needed. Include only paths that exist. Exclude dependencies, generated files,
and caches. Preserve deliberate rules and exclusions.

```php
<?php declare(strict_types=1);

use LaraStrict\Conventions\ExtensionFiles;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withRootFiles()
    ->withPaths(array_values(array_filter(
        ExtensionFiles::paths(baseDir: __DIR__, laravel11Structure: true),
        'file_exists',
    )))
    ->withSets([ExtensionFiles::Rector]);
```

```php
<?php declare(strict_types=1);

use LaraStrict\Conventions\ExtensionFiles;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withRootFiles()
    ->withPaths(array_values(array_filter(
        ExtensionFiles::paths(baseDir: __DIR__, laravel11Structure: true),
        'file_exists',
    )))
    ->withSets([ExtensionFiles::Ecs]);
```

Use `false` for `laravel11Structure` without these bootstrap files. Both
sets include the shared StrictPHP rules. Run Rector before ECS and PHPStan
in the quality workflow. Review Rector changes. After class renames,
move files to match their PSR-4 paths before PHPStan runs.

## 3. Configure PHPStan and Larastan

Create or merge `phpstan.neon`. Analyze the maintained application paths and
existing bootstrap files. Preserve the project's Larastan bootstrap,
reflection, and test integrations. LaraStrict container rules create
application objects, so load the application and its service providers before
analysis. Keep view paths for Blade checks, but exclude compiled views and
vendor templates.

```neon
parameters:
    level: max
    paths:
        - app
        - config
        - database
        - lang
        - routes
        - tests
        - bootstrap/app.php
        - bootstrap/providers.php
```

Remove any paths that do not exist, including optional `lang` and bootstrap paths. Use the Laravel version's installed
Larastan documentation for bootstrap configuration. Keep intentional existing
settings. Do not lower the level, disable rules, or add a baseline to make
setup checks pass.

## 4. Add the Composer scripts

Merge these scripts into the root `composer.json`. Keep the existing Laravel or
Pest test command and its environment setup. Preserve existing work in scripts
with the same names, then update dependent aliases as needed.

```json
{
    "scripts": {
        "qa": ["@lint", "@analyse", "@test"],
        "lint": ["@lint:upgrade", "@lint:fix"],
        "lint:upgrade": "vendor/bin/rector process",
        "lint:upgrade:check": "vendor/bin/rector process --dry-run",
        "lint:fix": "vendor/bin/ecs --fix",
        "lint:check": "vendor/bin/ecs",
        "analyse": "vendor/bin/phpstan analyse",
        "lint:stan": "@analyse",
        "test": "php artisan test"
    }
}
```

`composer qa` applies Rector, then ECS, runs PHPStan, and runs tests last. `lint`
changes files. It is not a read-only check. `analyse` is the PHPStan command;
`lint:stan` is its alias.

## 5. Install AI instructions and skills

Merge these scripts and hooks into `composer.json`. Keep existing hook commands
and their order. Append the sync hook once:

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

These paths use Composer's default `vendor` directory. Adapt both paths if the
project sets `config.vendor-dir`. The guarded hooks skip sync when development
dependencies are absent. The explicit check must fail if the command is
missing.

Run and check the initial sync:

```bash
composer strictphp:sync-ai
composer strictphp:check-ai
```

The sync installs the shared StrictPHP skill, LaraStrict skills, skills from
other packages, and package instructions registered by the root project. Keep
local project rules outside the managed root `AGENTS.md` block or in directory
instructions. Do not replace consumer copies with symlinks. Keep the generated
root `AGENTS.md` block, `.agents/skills/`, and
`.agents/.strictphp-manifest.json` in version control. Preserve local changes
if sync reports a conflict. Move local guidance out of generated files before
retrying. See [AI agent setup](../docs/ai-agents.md) for package and root
registrations.

## 6. Run checks locally and in CI

Run the read-only tool checks and the existing test runner listed below. Resolve
setup errors and rerun affected checks in this order: Rector, ECS, PHPStan, tests.
During installation, report existing code violations without applying code
transformations or fixing application code. Report existing violations separately
from setup errors. Do not report QA as passing while a check fails. Report blocked
checks with their cause.

For CI or a final check without code transformations, run:

```bash
composer lint:upgrade:check
composer lint:check
composer analyse
composer test
composer strictphp:check-ai
```

To check committed AI files in CI, install development dependencies with
`composer install --no-scripts` before the AI check. Otherwise, an automatic
sync can update stale files before the check reads them. Run required
application setup commands that skipped Composer hooks normally provide.

Report installed packages, configuration and script changes, generated AI
files, passing checks, and failed or blocked checks. Installing configuration
and resolving every violation in an existing application are separate tasks.

## Optional upgrade session

After installation and its validation report, ask: "Would you like to start an
upgrade session using `$larastrict` to bring the existing code into compliance?"
The `$larastrict` skill also applies the shared `$strictphp` guidance.
Wait for the user's answer. Installation approval alone does not authorize this
upgrade session.

If approved, agree on the scope and use the main skill with the relevant package
skills. Preserve application behavior. Run Rector first, then ECS, then PHPStan,
with tests last. Review transformations and move renamed files to their PSR-4
paths before PHPStan runs. Use `composer qa` for the agreed scope when appropriate;
it applies code changes. Report changes and any remaining failed or blocked checks.
