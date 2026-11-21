# Contributing

These instructions are for developing the package, not using it in an app.

## Documentation

The README and `docs/` are the source for both GitHub and the portfolio
viewer. Update `docs/navigation.json` when pages change, and use relative links.
Keep the `installation-command` comments around the command in `README.md`;
the portfolio uses that block for its package card. Preserve README section
anchors when moving existing content.

## Compatibility

Package sources and test helpers keep PHP 7.4-compatible syntax. The package
supports PHP 7.4 through the current PHP 8.x releases and Laravel 7–13. Laravel's
own PHP requirements still apply:

| Laravel | Package PHP minimum | Testbench |
| ------- | ------------------- | --------- |
| 7, 8    | 7.4                 | 5, 6      |
| 9       | 8.0.2               | 7         |
| 10      | 8.1                 | 8         |
| 11, 12  | 8.2                 | 9, 10     |
| 13      | 8.3                 | 11        |

The root lockfile retains the PHP 7.4 / Laravel 7 development baseline. CI tests
Laravel 7.0 separately and resolves dependencies for every supported Laravel
major, covering PHP 7.4 and PHP 8.0–8.5. Real browser checks run at both ends of
the framework range. See [VERIFICATION.md](VERIFICATION.md) for actual local
results and checks that have not run remotely.

## Install development tools

Use PHP 7.4 for the root lockfile and baseline analysis. Pint runs separately
on PHP 8.3+. Substitute the executable paths available on your system:

```sh
php7.4 /path/to/composer install
php7.4 /path/to/composer install --working-dir=tools/analysis
php8.3 /path/to/composer install --working-dir=tools/pint
```

Larastan 0.7.15 and its PHPUnit extension are isolated in `tools/analysis` with
Laravel 7 dependencies. The analyzer targets PHP 7.4 at level 8 and loads the
root application's autoloader. Run it against the baseline installation; it is
not a modern-Laravel analyzer. Isolation lets Testbench and PHPUnit resolve newer
Laravel versions without the legacy analyzer restricting their dependencies.
Both tooling directories are excluded from Composer distributions.

The legacy test/tool dependencies have upstream security advisories. The root
and analysis project's `config.audit.block-insecure=false` permits this required
legacy compatibility testing; it does not change consumers' Composer settings.
Review `composer audit` when assessing a release.

## Run checks

```sh
PINT_PHP_BINARY=/path/to/php8.3 php7.4 /path/to/composer check

npm ci
PHP_BINARY=/path/to/php7.4 npm test
npx playwright install chromium
PHP_BINARY=/path/to/php7.4 npm run test:browser
```

`composer check` checks formatting, runs Larastan and executes PHPUnit. Separate
commands are `composer format`, `composer format:check`, `composer typecheck`
and `composer test`. Test and analysis scripts use Composer's PHP interpreter.
Use `PLAYWRIGHT_CHANNEL=chrome` for an installed Chrome browser.

The tests cover the core, Laravel validation, Blade rendering, config publishing
and cached configuration. Browser tests render fixtures through Laravel and
exercise the distributed asset. A clean archive consumer can be checked with:

```sh
LARAVEL_VERSION='^7.0' php7.4 scripts/consumer.php
```

For a different framework version, use a disposable checkout and a compatible
PHP runtime. This example selects Laravel 13:

```sh
composer require --dev 'laravel/framework:^13.0' 'orchestra/testbench:^11.0' --no-update
composer update --prefer-dist --no-interaction
composer test
npm test
```

Use the same PHP runtime for Composer and `PHP_BINARY` during browser checks.
Do not run the legacy baseline analyzer against the newer dependency install.

## Formatting

VS Code settings choose Intelephense for PHP format-on-save. CLI Pint uses the
Laravel preset with matching overrides. Preserve logical blank lines and expanded
associative arrays, and keep package syntax compatible with PHP 7.4. Recheck
formatter alignment when updating either tool; preset names alone do not imply
identical output. No global editor settings need to change.

## Implementation rules

[CONTRACT.md](CONTRACT.md) records the detailed parsing, validation and selector
rules shared by the Laravel and TypeScript implementations. Maintainers use it
with the shared fixtures to prevent differences between packages. Package users
do not need to read it to get started. Neither normal tests nor runtime depends
on the sibling repository.

Data provenance and correction instructions are in [data/README.md](data/README.md).
[RELEASING.md](RELEASING.md) describes the separate release-review process.
