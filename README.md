# LaraStrict conventions for your Laravel apps

![.github/banner.webp](.github/banner.webp)

Enhance the robustness and consistency of your Laravel applications with LaraStrict conventions. This package integrates essential tools like PHPStan, Easy Coding Standard, RectorPHP, and tailored rules to streamline your development process.

This package extends the [StrictPHP conventions](https://github.com/strictphp/conventions).

## Key Features

This package bundles a selection of powerful tools and configurations to support your development workflow:

- **PHPStan Configuration and Custom Rules**: Integrates [PHPStan](https://phpstan.org), with pre-defined configurations
  and rules tailored for our projects.
- **Easy Coding Standard Configuration**: Utilizes [Easy Coding Standard](https://github.com/symplify/coding-standard)
  for enforcing consistent coding styles and practices.
- **PHPUnit 9 Support**: Ensures compatibility with [PHPUnit 9](https://phpunit.de) to facilitate comprehensive testing.
- **RectorPHP Configuration**: Provides configurations for [RectorPHP](https://getrector.org) for better code quality.
- **Extended PHPStan Packages**: Includes additional packages to augment PHPStan's capabilities. For more details, see
  the included `composer.json` file.

## Prerequisites

Before installation, ensure your environment meets these requirements:

- PHP version 8.1 or higher.
- Composer
- Laravel 9+
- Enabled [Extension installer for PHPStan](https://github.com/phpstan/extension-installer), which is automatically installed by our package to allow extension discovery.


## Installation

To integrate LaraStrict Conventions into your project, run the following command in your terminal:

```bash
composer require larastrict/conventions --dev
```

This installs the package as a development dependency. During installation, you'll be prompted to confirm the installation of the plugin:

> Do you trust "phpstan/extension-installer" to execute code and wish to enable it now? (yes/no) [y]:

Type `y` to use all provided extensions.

## Getting Started

After installation, you can tailor the provided configurations to meet your project's requirements.

### Setting Up Easy Coding Standard

To use Easy Coding Standard, create or update an `ecs.php` file at your project's root with the following setup:

```php
<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withRootFiles()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    // This is required to include the StrictPHP Conventions
    ->withSets([\LaraStrict\Conventions\ExtensionFiles::Ecs]);
```

### Configuring RectorPHP

For integrating RectorPHP, add or update a `rector.php` file in your project's root with the following configuration:

```php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withRootFiles()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    // This is required to include the StrictPHP Conventions
    ->withSets([\LaraStrict\Conventions\ExtensionFiles::Rector]);
```

### Integrating PHPStan

Ensure you use the PHPStan extension installer to install the required extensions.

### Rules

The package includes the following rules:

#### UsableInContainerRule

> **Customization does not work yet. Currently disabled**

The package includes rules for ensuring DI classes/contracts are used appropriately:

- **Ensures Contracts are Injectable**: Checks if an implementation is registered for the contract.
- **Ensures Classes are Injectable**: Checks if a registered class has injectable dependencies.

You can customize these settings as needed. Example configuration:

```neon
services:
    -
        class: LaraStrict\Conventions\PHPStan\UsableInContainerRule
        tags: [phpstan.rules.rule]
        arguments:
            enabled: true
            # Ensure that classes that extends these classes are injectable
            extends:
                - Illuminate\Console\Command
                - Illuminate\Database\Seeder
            # Do not check if classes that has these suffixes are injectable
            excludeSuffixes:
                - MySuffix
            # Check classes in these namespaces
            namespaces:
                - \DIClasses\
            # Do not check if classes file contains these folders
            excludeFolders:
                - my_folder
```


Default settings can be found [in ./extension.neon file](./extension.neon)


