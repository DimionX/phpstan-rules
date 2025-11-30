# PHPStan Extension Rule

[PHPStan](https://github.com/phpstan/phpstan) rule to detect usage of Composer dev-dependency classes in production code, with powerful configuration to re-allow specific classes or namespaces in places where they should be allowed.


## Installation

Install the extension using [Composer](https://getcomposer.org/):

```bash
composer require --dev dimionx/phpstan-rules
```

## Manual installation

For manual installation, add this to your `phpstan.neon`:

```yaml
includes:
    - vendor/dimionx/phpstan-rules/extension.neon
```

## Ignoring Errors

Only ignore errors for code that **never runs in production**. Common examples:

- **Test files** (`*Test.php`, `*/Tests/*`, `*/tests/*`)
- **Test helpers & utilities** used exclusively in tests
- **Development scripts & tools**
- **Fixture factories** for testing only
- **Code generation scripts** used during development

```yaml
# phpstan.neon
parameters:
  ignoreErrors:
    -
      identifier: dev.packageUsedInProductionRule
      path: '*/Tests/*'
```

## Features

- **Detects accidental usage** of dev-dependency classes in production code
- **Configurable autoload types** (PSR-4, PSR-0, classmap, files)
- **Namespace-based detection** for comprehensive coverage
- **Flexible allowlists** for legitimate cross-environment usage
- **`composer.lock` analysis** for accurate dependency mapping