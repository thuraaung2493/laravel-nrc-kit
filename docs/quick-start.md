# Quick start

Start with the [overview example](../README.md#basic-usage), then inspect the parsed components.

```php
use Thuraaung\NrcKit\Nrc;

Nrc::from('12/LaMaNa(N)001234')->parse('mm');
// [
//     'state' => '၁၂',
//     'stateName' => 'ရန်ကုန်တိုင်း',
//     'township' => 'လမန',
//     'type' => 'နိုင်',
//     'typeName' => 'နိုင်ငံသား',
//     'number' => '၀၀၁၂၃၄',
//     'language' => 'mm',
// ]
```

`parse()` returns `null` for invalid input. Its default `auto` language follows
the input's township script; `en` or `mm` selects the returned language. The
returned `language`, `stateName` and `typeName` describe that output. Keep serial
numbers as strings to preserve leading zeroes.

Supported types are `N`/`နိုင်` (Full Citizen), `E`/`ဧည့်` (Associate Citizen),
`P`/`ပြု` (Naturalized Citizen) and `M`/`သ` (Monk). `AE` is an alias of `E`.
Nun is not supported. A valid NRC also needs a known state/township pair and
exactly six digits. Validation does not verify identity or document issuance.

See the [API reference](api-reference.md) for additional helpers.
