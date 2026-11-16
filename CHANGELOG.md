# Changelog

## 0.1.0 — 2026-11-16

- Breaking: added E/AE (Associate Citizen) and M (Monk); N remains Full Citizen
  and Nun is unsupported. Added localized region/type names to parsing; returned
  `language` now describes the output language.
- Breaking: states return `stateNo`/`name`, types return `en`/`mm`/localized `label`,
  and townships return bilingual pairs by default or strings for a language.
  Removed the separate township-options API. State 9 lists both regions and has
  one combined selector option. Numeric state IDs must be safe integers.

- Moved all Laravel NRC reference records into the publishable PHP configuration.
  Replaced `additional_data` merging with the complete editable `data` array and
  removed the Laravel JSON dataset. Defaults retain all original values/order.

- Added PHP 8.x and Laravel 8–13 support while retaining PHP 7.4 and Laravel
  7.0 compatibility, with per-version tests and isolated baseline analysis tools.
- Initial offline bilingual NRC engine and bundled, checksummed reference snapshot.
- Localized native selector, strict validation and independent package tests.

- Added enforced formatting and static-analysis tooling with independent CI checks.
- Added publishable Laravel configuration for append-only bilingual reference
  extensions, with conflict detection and shared core/selector behavior.
