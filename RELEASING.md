# Independent releases

This repository owns `thuraaung2493/laravel-nrc-kit`, its changelog and independent
`vX.Y.Z` tags. Composer/Packagist derive versions from authorized Git tags; no
hardcoded Composer version field is used. The manual release-check workflow
builds a review archive only; it does not publish, tag, push or create releases.

Before release, run `composer validate --strict`, `composer install`,
`composer typecheck`, `composer test`, `composer format:check` (PHP 8.3 tool), `npm ci`, `npm test`, `php scripts/consumer.php` and
`composer audit`. Review legacy-framework advisories, provenance/checksum,
archive contents, bundled browser assets and the full PHP/Laravel compatibility
matrix. Baseline type checking requires the isolated `tools/analysis` install;
see CONTRIBUTING.md. Verify both Laravel 7.0/PHP 7.4 and Laravel 13 on its supported
PHP versions before claiming the complete support range.
Do not widen constraints to untested versions. Data or behavior corrections need
parity review and possibly a separate JS change; versions are independent.

Commits, pushes, tags, Packagist registration/webhooks and GitHub releases require
separate explicit authorization. No release credentials are stored here. Do not
commit the planning parent's SPEC.md automatically or release from that parent.
