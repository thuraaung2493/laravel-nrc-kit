# Blade selector

Publish the browser asset before using the selector. No Node or frontend
framework is required.

```sh
php artisan vendor:publish --tag=nrc-kit-assets
```

```blade
<x-nrc-selector
    name="nrc"
    :value="old('nrc', $client->nrc ?? null)"
    language="mm"
    required
/>
```

`language` accepts `en`, `mm` or `auto` (default). The selector submits one
complete NRC under `name` (default `nrc`) and supports `disabled`, `id`, `class`
and `error`. It displays Laravel validation errors automatically. Validate the
submitted value with `ValidNrc` on the server.

To switch language in the browser, give the selector an `id` and call:

```js
window.NrcKit.setLanguage(document.getElementById("your-selector-id"), "mm");
```

Selections and leading zeroes are preserved. For dynamically inserted selectors,
call `window.NrcKit.init(element)` after the asset has loaded.
