# Tool setup

[Back to README](../README.md)

Install the package as a development dependency:

```bash
composer require --dev larastrict/conventions
```

Allow `phpstan/extension-installer` when Composer asks. The plugin loads the
package's PHPStan rules. Do not also include `extension.neon` manually when the
plugin loads it.

## PHPStan

Create `phpstan.neon` in the application root. Select the paths used by your
application. For example:

```neon
parameters:
    level: max
    paths:
        - app
        - config
        - database/migrations
        - database/seeders
        - routes
        - tests
        - bootstrap/app.php
        - bootstrap/providers.php
```

All LaraStrict rules are enabled by default. Container checks create application
objects, so the application and its service providers must be loaded first.
Use the application's existing PHPStan bootstrap setup. See
[PHPStan rules](phpstan.md) to change individual rules.

Run the checks:

```bash
vendor/bin/phpstan analyse
```

The package uses PHPStan's native directory dependency tracking for its rule
architecture and view helper sources. Changes in a local or symlinked checkout invalidate cached results,
even when `composer.lock` does not change. This keeps full-project checks in sync
with the current rules. A single-file analysis does not refresh the full-project
result cache.

For speed, keep the analysis paths stable and preserve PHPStan's `tmpDir` in CI.
PHPStan reuses unchanged analysis results; a separate Laravel filesystem or
compiled-view cache is not needed for the Blade checker. See
[PHPStan result caching](https://phpstan.org/user-guide/result-cache).

`PHPStan\Services\ViewTemplateService` reuses its injected filesystem and creates
its parser and Blade compiler lazily, once per worker. It compiles strings in
memory and resets the compiler after an error. Its source is covered by the
native PHPStan dependency tracking above.

## Easy Coding Standard

Create `ecs.php` in the application root:

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

Use `laravel11Structure: true` when the application has `bootstrap/app.php` and
`bootstrap/providers.php`. Use `false` for applications without these bootstrap files.
Include only paths that exist.

Check or fix formatting:

```bash
vendor/bin/ecs
vendor/bin/ecs --fix
```

The [ECS configuration](../extension-ecs.php) includes the shared StrictPHP rules.
It orders class members as follows: trait uses and constants, static properties,
instance properties, the constructor, then other methods. Members within each
group keep their relative order, regardless of visibility.

In concrete `*Action` and `*Query` classes under `App` or `LaraStrict`, `execute()`
comes directly after the constructor and before other methods. This also applies
to endpoint slices. Abstract classes keep their method order. Other class roles,
interfaces, traits, and anonymous classes do not get the `execute()` priority.
The formatter preserves member comments, attributes, and promoted parameters.
It skips Blade files and sets braces, indentation, and blank lines. PHP files start
with `<?php declare(strict_types=1);`.

## Rector

Create `rector.php` in the application root:

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

Use the same path setting as ECS. Preview or apply changes:

```bash
vendor/bin/rector process --dry-run
vendor/bin/rector process
```

The [Rector configuration](../extension-rector.php) includes the shared StrictPHP
rules, class naming rules, and skips Blade files. Review changes before committing
them. Rector updates declarations and PHP references. After applying class-name
changes, move each renamed file to match its PSR-4 namespace and class name before
running PHPStan.

## Release contents

The StrictPHP dependency owns the shared `strictphp-sync-ai` executable in its
`bin/` directory. LaraStrict uses that command to sync registered instructions
and skills. The LaraStrict release includes all `src/` and `resources/`
files, the extension configurations, documentation, and license.

For the complete project setup, including CI and Composer hooks, follow the
[LaraStrict setup guide](../doc/setup-guide.md).

Git `export-ignore` attributes and Composer `archive.exclude` omit GitHub/GitLab
metadata, local skill symlinks, IDE files, fixtures, tests, installed dependencies,
and development-only configuration. Keep both exclusion lists aligned.

Run `vendor/bin/phpunit --filter ReleaseArchiveTest` after changing the package
layout or release filters. It checks Git and Composer archives for runtime files and excludes
development files. StrictPHP tests the shared sync command in its own archives.
