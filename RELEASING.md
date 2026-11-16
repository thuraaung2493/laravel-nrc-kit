# Releases

This repository independently releases `thuraaung2493/laravel-nrc-kit`.
Packagist publishing is already automated: the active GitHub push webhook
notifies Packagist, which reads versions from this repository's Git tags.
No Composer upload command, Packagist API token in CI, or repeated package
submission is needed. Keep the version field absent from `composer.json`.

## Release a version

Update `CHANGELOG.md`, commit and push to `main`, and wait for all PHP/Laravel
CI jobs to pass. Then create and push the version tag. For example, after
preparing version 0.1.1:

```sh
git tag -a v0.1.1 -m 'Release 0.1.1'
git push origin v0.1.1
```

Packagist receives the tag automatically. Verify the new version on
[the package page](https://packagist.org/packages/thuraaung2493/laravel-nrc-kit).
Packagist indexes pushed tags independently of GitHub Actions, so create release
tags only on commits whose CI has passed. Do not move or reuse release tags.

The webhook points to `https://packagist.org/api/github` and subscribes to
GitHub push events. Packagist reports the package as auto-updated. Its initial
submission is complete; future releases need no manual registry interaction.

## Release checks

CI covers PHP 7.4 through 8.5 and Laravel 7 through 13, including the minimum
Laravel 7.0 release. It runs syntax checks, Larastan, PHPUnit, Pint, clean
Composer archive consumers and browser checks at the supported endpoints.
Existing runtime and framework requirements remain unchanged.

For local checks, follow `CONTRIBUTING.md`. Baseline analysis uses PHP 7.4 and
the isolated `tools/analysis` installation; Pint uses its PHP 8.3 tool.
Review `composer audit`, reference-data provenance/checksums and archive contents
before releasing. Legacy development-stack advisories are documented there.

The manual `release-check.yml` workflow produces a review archive only. It does
not create tags, publish packages or create GitHub releases. Data or behavior
changes need parity review against the TypeScript implementation; versions and
releases remain independent.
