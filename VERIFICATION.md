# Verification record — 2026-11-14

Implementation followed the shared planning specification in phases. The parent
SPEC.md was not copied or modified.

1. **Data/contract:** 14 state numbers, 429 township pairs, no duplicate codes
   within a state, pinned source revision and matching SHA-256. Exact mixed-script
   behavior, whitespace and error shapes are recorded in CONTRACT.md.
2. **Core:** the initial PHP 7.4 core suite passed 8 tests / 5,326 assertions,
   including all-township round trips and aliases before adapters were added.
3. **Laravel adapter:** final **12 tests / 7,100 assertions passed** on actual
   **PHP 7.4.33 + Laravel 7.30.7**, using PHPUnit 9.6.39 and Testbench 5.20.0.
   Tests cover the Rule through Laravel Validator; component alias, localized
   render, required/disabled, single submitted value, flashed old() input,
   validation errors, unique IDs, multiple components and escaping.
4. **Browser controller:** **25 independent jsdom checks passed**, rendering
   shared presets through Laravel and exercising the shipped JavaScript asset.
   Native reset after state/language switching has regression coverage.
5. **Distribution:** `composer validate --strict` passed. `scripts/consumer.php`
   archived this package, verified bundled data/views/browser script, installed
   the extracted package into an isolated Composer consumer with no dev
   dependencies, and executed conversion from its own installed snapshot.

Real Chrome/Chromium **157.0.8103.52** tests passed for localized native form
submission, leading zeroes/mixed digits, dependent township clearing, keyboard
focus, required validity, disabled submission and multiple independent instances.
No consumer Node build or frontend framework is needed. Development-only npm
checks contain no React or Zod dependency.

The optional local parity comparison matched the independent JS engine on
**3,455 inputs**, every township/type, all localized parsed components and
metadata, conversions, structured validation errors, options and identical
snapshot/fixture/contract bytes. Standard tests/CI use only this repository.

The mandatory legacy test stack's Composer audit reports **26 advisories** in
`composer/composer`, `laravel/framework`, `league/commonmark` and
`league/flysystem` (the Composer library is a legacy Larastan development dependency). PHP 7.4/Laravel 7
are end-of-life. The compatibility project's audit resolver override permits
installing that required legacy test stack; audit results are not hidden, and
the setting does not propagate to a consumer. No newer PHP/Laravel support is
advertised until tested. No runtime network calls or NRC logging are included.

CI and manual artifact-readiness workflows are configured independently. Remote
CI has not run: GitHub origins are private empty repositories and source remains
local/uncommitted. No pushes, tags, package publications or releases were made.
Only Chromium is browser-verified. The historical community reference is not a
current official registry and cannot verify identity or issuance.

## Code quality follow-up

- Pint 1.32.3 (Laravel preset), isolated on PHP 8.3: formatting applied and
  read-only check passed. No PHP 8 syntax was introduced into package sources.
- Larastan 0.7.15 / PHPStan 0.12.100, level 8, PHP 7.4 target: no errors across
  source, tests and PHP scripts. PHPUnit extension 0.12.22 narrows assertions.
- `composer check` passed using PHP 7.4 and `PINT_PHP_BINARY` pointing to PHP 8.3:
  Pint check, Larastan and all 12 PHPUnit tests / 7,100 assertions.
- All PHP files passed the actual PHP 7.4 syntax checker; 25 Blade browser
  controller checks still pass. Shared fixture/data/contract bytes are retained.
- Runtime package requirements are unchanged. Formatter dependencies remain
  isolated, development-only and excluded from the distribution. CI has separate
  formatter and compatibility-test jobs. The legacy analyzer emits its upstream
  old-version notice; it is pinned to the supported Laravel 7/PHP 7.4 combination.

## Intelephense alignment follow-up

VS Code workspace settings now choose Intelephense for PHP format-on-save,
with PER braces and PHP 7.4 analysis. Pint's concatenation rule uses one space
on each side to match the editor. The workspace disables the Pint editor
extension while retaining CLI/CI checks. The actual installed Intelephense
1.18.5 language server formatted all 12 PHP source/test/script files locally;
logical grouping and expanded arrays were preserved. No package dependencies,
runtime support ranges, public APIs or global editor settings were changed.

## Laravel configuration extension — 2026-11-15

`config/nrc-kit.php` now provides `additional_data`, merged by the provider and
publishable as `nrc-kit-config`. The effective reference is shared by the core,
validation rule, lookups and Blade browser payload. The checksummed snapshot is
unchanged; default parity with TypeScript remains the contract. No TypeScript
source or extension API was changed.

Configuration coverage includes custom pairs/new states, both scripts and
conversion directions, strict serial/type/state validation, all lookup shapes,
Blade prefill, duplicate preservation, malformed/conflicting records, changed
configuration, copy isolation, fresh application containers and Laravel 7's
cached-configuration bootstrap. The browser suite renders application-defined
records through Laravel and verifies localized complete submission.

Final verification passed:

- Pint formatting check and Larastan level 8.
- PHPUnit on PHP 7.4: 31 tests / 7,168 assertions.
- Blade selector controller: 26 checks; real Chrome 157.0.8103.52 submission,
  localization and application-defined state/township selection passed.
- Unconfigured PHP/TypeScript parity: 3,455 cases; shared snapshot and fixture
  bytes remain unchanged.
- PHP 7.4 syntax checks, strict Composer manifest validation, and clean-consumer
  installation from a local Composer archive containing the publishable config.

No packages were published and no Git pushes, tags or releases were made.

## PHP and Laravel compatibility expansion — 2026-11-15

Composer now accepts PHP `^7.4 || ^8.0` and Illuminate majors 7–13, including
Laravel 7.0. Source syntax remains PHP 7.4-compatible. The root lockfile keeps
Laravel 7.30.7 and PHPUnit 9.6 as the reproducible baseline; separate Testbench
and PHPUnit ranges allow modern development installs. Larastan and its PHPUnit
extension moved to a locked, isolated `tools/analysis` project with Laravel 7.
It checks the baseline only and no longer restricts newer framework installs.

Two actual Laravel 7.0 differences were corrected: the Blade component uses the
class-first registration signature, and the asset uses a view-stack guard in
place of the later `@once` directive. Tests verify one asset per render, including
multiple selectors and separate renders. The CLI fixture renderer bootstraps an
application directly without invoking PHPUnit's test-runner lifecycle. Invalid
configuration cases run in a loop so coverage does not depend on metadata removed
in newer PHPUnit versions; all 13 invalid/conflict scenarios remain exercised.

Every row below passed the full suite (20 tests / 7,157 assertions) and configured
Blade rendering in an isolated dependency installation:

| PHP    | Laravel | Testbench | PHPUnit |
| ------ | ------- | --------- | ------- |
| 7.4.33 | 7.0.0   | 5.0.0     | 9.6.39  |
| 7.4.33 | 7.30.7  | 5.20.0    | 9.6.39  |
| 7.4.33 | 8.83.29 | 6.48.0    | 9.6.39  |
| 8.3.33 | 9.52.23 | 7.58.0    | 9.6.39  |
| 8.3.33 | 10.50.4 | 8.39.0    | 10.5.67 |
| 8.3.33 | 11.57.1 | 9.18.0    | 12.5.44 |
| 8.3.33 | 12.72.0 | 10.12.0   | 12.5.44 |
| 8.3.33 | 13.42.0 | 11.3.0    | 12.5.44 |
| 8.5.12 | 13.42.0 | 11.3.0    | 13.4.7  |

Both endpoint applications (Laravel 7.0/PHP 7.4 and Laravel 13/PHP 8.5) also passed
all 26 browser-controller checks and real Chrome 157.0.8103.52 tests, including
configured records, localized submission, keyboard navigation and form validity.
Clean local Composer archive consumers passed with Illuminate 7.0.0/PHP 7.4 and
Illuminate 13/PHP 8.5.

The baseline `composer check` passed Pint, Larastan level 8 and PHPUnit. All
package PHP files passed the PHP 7.4 syntax checker; both manifests and lockfiles
passed strict Composer validation. Workflow YAML and support constraints were
validated locally.

The installed Intelephense 1.18.5 formatter agreed with Pint on all 14 package
PHP files without further edits. Default cross-language parity still passed
3,455 inputs; configured parity passed nine inputs and localized lookups.

CI now contains the locked baseline plus 15 dependency-resolution cases spanning
PHP 7.4 and 8.0–8.5 with compatible Laravel versions. PHP 8.0, 8.1, 8.2 and 8.4 are
not installed locally; those runtime checks are configured but have not run.
Remote CI has not run because nothing was pushed. Future Laravel majors remain
excluded until their compatibility is checked. No packages, releases or tags were
published.

## Laravel dataset stored only in PHP config — 2026-11-15

All 14 state groups and 429 township pairs now live directly under `data` in
`config/nrc-kit.php`. Laravel's `data/nrc.json` was removed. The published config
is the complete application dataset, with no append-only merge step. Applications
can edit, add or remove mappings; an explicit empty array accepts no NRCs.
The former `additional_data` option is replaced by `data`.

Defaults preserve every original value and its ordering. The normalized array
fingerprint is pinned in CoreTest; the optional parity checker compares PHP's
config records with TypeScript's decoded JSON. TypeScript's runtime, dataset and
extension API are unchanged; only its maintainer parity script/data notes needed
to describe the different storage formats.

Final checks passed:

- Full suite: 22 tests / 7,166 assertions on all nine PHP/Laravel dependency
  combinations in the compatibility table above, including Laravel 7.0 and 13.
- Coverage includes provider registration with replacement and empty data,
  modified/removed mappings, configuration order, strict validation, duplicates,
  fresh containers, runtime changes, published defaults and cached configuration.
- All 26 selector checks and real Chrome 157.0.8103.52 checks passed separately
  on Laravel 7.0/PHP 7.4 and Laravel 13/PHP 8.5. Configured browser options exclude
  removed default records, and localized complete submission remains valid.
- Clean local archive consumers passed on Illuminate 7.0/PHP 7.4 and Illuminate
  13/PHP 8.5. Archive checks require the full PHP config and reject a JSON dataset.
- Baseline `composer check`: Pint, Larastan level 8 and PHPUnit passed. PHP 7.4
  syntax and strict Composer manifest checks passed.
- Default cross-language parity passed 3,455 inputs; configured parity passed
  nine inputs and localized lookups. Shared fixtures/contract remain byte-identical.

The README now explains publishing and editing the complete configuration,
migrating `additional_data`, rebuilding config caches and maintaining published
copies. No dependencies, version constraints, package versions, publications,
pushes, tags or releases were changed in this storage refactor. The unrun remote
CI/runtime checks noted above remain unrun.

## Named references, new types and localized parsing — 2026-11-15

Laravel implements the same new public shapes and output-language semantics as
TypeScript. `Nrc::townshipOptions` is removed; `Nrc::townships(state)` returns
bilingual pairs and an explicit language returns code strings. Parsing returns
localized region/type names and an output `language`. The catalog includes
N/E/P/M, with AE aliasing E; Nun remains unsupported.

All default names, classifications and descriptions are stored directly in
`config/nrc-kit.php` under `states` and `types`, alongside the unchanged 429
bilingual township pairs under `data`. Type definitions have English `label`
and Myanmar `labelMm`; public `Nrc::types(language)` returns one localized
`label`. Published configuration remains fully editable and config-cache safe.
The selector detects original preset language separately from canonical parts,
combines shared state number 9, and uses the active metadata and type catalog.

Verification passed:

- `composer check`: Intelephense-aligned Pint, Larastan level 8 and 29 PHPUnit
  tests / 12,442 assertions on PHP 7.4. Fifteen PHP files match the installed
  Intelephense 1.18.5 formatter; all pass PHP 7.4 syntax checking. Strict Composer
  manifest validation passed.
- All nine local PHP/Laravel combinations in the compatibility table above
  passed the full 29-test suite and configured Blade rendering, including
  Laravel 7.0 and 13 on compatible installed PHP 7.4, 8.3 and 8.5 runtimes.
- All 33 Blade controller tests passed, including all shared type fixtures,
  default/configured data, localization, required/disabled behavior and reset.
- Real Chrome 157.0.8103.52 on Laravel 7.0/PHP 7.4 and Laravel 13/PHP 8.5
  verifies new E/M type submission, one state-9 option, named regions, keyboard
  access, independent fields and configuration data.
- Clean Composer archive consumers passed on Illuminate 7.0/PHP 7.4 and
  Illuminate 13/PHP 8.5. They check exact localized parsed fields, new type
  conversions, lookup shapes, removed methods and bundled assets. The archive
  still contains the full PHP config and no Laravel JSON dataset.
- Optional cross-language parity passed 6,036 default inputs and 14 configured
  cases, with identical names, type descriptions, public lookup shapes, records
  and classifications. Contract/fixtures are byte-identical.

Configuration tests also exercise custom descriptions, full replacement data,
malformed metadata, duplicate types, unsafe numeric state IDs and cached config.
Remote CI and the additional unavailable PHP runtime jobs remain unrun as noted
above. No dependency constraints, package versions, publications, pushes, tags
or releases changed in this API update.

## GitHub source and branding — 2026-11-15

The initial implementation was committed as `9a69457` and pushed to the private
[GitHub repository](https://github.com/thuraaung2493/laravel-nrc-kit).
[The initial CI run passed](https://github.com/thuraaung2493/laravel-nrc-kit/actions/runs/43709712274).

All 17 CI jobs passed: the PHP 7.4/Laravel 7 baseline, 15 compatibility combinations through PHP 8.5/Laravel 13, and Pint. Local Composer checks also passed (29 tests, 12,442 assertions and Larastan).

Created a shared transparent Blackmonster logo and matching package banner with
the built-in image-generation tool. The README displays the banner and links to
the author profile. npm/Composer archive checks confirm the image files are
included with the README. Laravel's shipped selector JavaScript is tracked;
its ignore rule now applies only to the root build directory.

GitHub does not offer a first social-preview upload for a new private repository,
so the banner is used in the README. The repositories remain private.
Generation prompts are recorded in `assets/branding/README.md`.

No registry packages, version tags or GitHub releases were published.
