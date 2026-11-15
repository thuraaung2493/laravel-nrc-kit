# Reference data

Laravel's complete default reference lives in `config/nrc-kit.php`, under `data`.
There is no `nrc.json` file in this package. Publishing `nrc-kit-config` gives
applications the full editable PHP array. Application changes replace the data
used by all NRC methods, the validation rule and the Blade selector.

The 14 states and 429 township pairs come from
`thuraaung2493/mm-nrc-converter`, revision
`28312a9e97e8d37a4205ea05fe05cf63635cf3ba`, verified on 2026-11-14. The original
JSON source SHA-256 was
`5230aa59fb37d5d9c5bbb5fc13464ae94a8bfad51af1086353fc8e475ac8cf18`.
Moving the records into PHP did not change their values or order. The default
array's compact UTF-8 JSON encoding (unescaped Unicode and slashes) has SHA-256
`009e65cfa1a01d8a5f4ad710acf32e766b8fca1f913917203db8338d5eaf11bc`;
tests pin this representation to detect data changes independently of PHP layout.

This is a historical community reference, not an official or exhaustive registry
of issued documents. No geographic names or new codes have been inferred.
State 9 combines Mandalay and Naypyidaw entries.
The inherited ISC attribution is retained in LICENSE.

The TypeScript repository maintains the canonical reference as `data/nrc.json`.
Approved corrections need cited evidence and maintainer review. Apply corrections
there and to the PHP array in this package, preserving values and order, update
the corresponding data fingerprints, and run each repository's own tests. The
optional parity script compares decoded records across the different storage
formats. Neither package needs a sibling checkout for normal use or tests.

Published application configurations are independent copies. Their owners must
review and apply future dataset corrections. Custom mappings do not certify a
person's identity or document issuance.

## Names and types

The same PHP config includes `states` and `types`; there are no Laravel JSON
metadata files. Region records have `stateNo`, English/Myanmar names (`en`, `mm`)
and optional `townships` code classifications for the shared state number 9.
Type records have `en`, `mm`, English `label` and Myanmar `labelMm` descriptions.
Public `Nrc::types(language)` returns `en`, `mm` and a localized `label` only.

Defaults use N (Full Citizen), E (Associate Citizen, alias AE), P (Naturalized
Citizen) and M (Monk). Nun is not supported by the default catalog. These English
letters are the approved package conventions, not a claim of a universal official
transliteration. The administrative split for state 9 is consistent with the
[Naypyitaw Council reference](https://myanmar.gov.mm/naypyitaw-council).

Parsing chooses Naypyidaw or Mandalay from the known township classifications;
custom unclassified state-9 codes receive the combined name. Unknown application
state names fall back to localized digits. Review the `states` metadata when
renaming or adding township codes in a published configuration. Keep approved
metadata changes synchronized with TypeScript's `data/nrc-metadata.json` and run
the optional parity check after each package's own tests.
