# Laravel validation

```php
use Thuraaung\NrcKit\Rules\ValidNrc;

$request->validate([
    'nrc' => ['required', new ValidNrc()],
]);
```
