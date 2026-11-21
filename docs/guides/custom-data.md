# Customize NRC data

Publish the full dataset to `config/nrc-kit.php`:

```sh
php artisan vendor:publish --tag=nrc-kit-config
```

The file contains all 14 states and 429 township pairs directly as a PHP array:

```php
return [
    'data' => [
        [
            'state' => '1',
            'townships' => [
                [
                    'en' => 'MaKaNa',
                    'mm' => 'မကန',
                ],
                // Other township pairs...
            ],
        ],
        // Other states...
    ],
];
```

Edit, add or remove records in this array. All methods, `ValidNrc` and the Blade
selector use it automatically. There is no separate JSON dataset or merge step.
Configuration order is preserved; an empty `data` array accepts no NRCs.

States are positive English digit strings without leading zeroes and must fit
a safe integer. Each township
needs an English letter code (`en`) and a Myanmar code (`mm`), without spaces
or separators. Duplicate states or township codes and malformed records throw
an `InvalidArgumentException`. The config also contains `states` for bilingual region names and `types` for
codes and descriptions (`label` in English, `labelMm` in Myanmar). Six-digit
serial rules stay fixed.

After deployment changes, rebuild your Laravel configuration cache and restart
long-running workers. Render selectors again to load changed data. A published
copy is maintained by your application and does not automatically receive future
dataset updates from this package.

If you used the earlier `additional_data` option, move those records into the
full `data` array; that option has been replaced.

See [data notes](../../data/README.md) for the bundled dataset's source and limitations.
