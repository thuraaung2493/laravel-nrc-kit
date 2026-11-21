# API reference

Use `Nrc::from($input)` to parse, validate, or convert an NRC.

| Helper                             | Purpose                                                              |
| ---------------------------------- | -------------------------------------------------------------------- |
| `Nrc::from($input)->convert('mm')` | Convert to the selected language                                     |
| `Nrc::from($input)->isInvalid()`   | Check whether an NRC is invalid                                      |
| `Nrc::from($input)->validate()`    | Return `valid` and `errors` with `code`/`message`                    |
| `Nrc::fromParts($parts)`           | Build an NRC from string `state`, `township`, `type`, `number` parts |
| `Nrc::states('mm')`                | Named regions: `stateNo`, `name`                                     |
| `Nrc::townships(12, 'mm')`         | Myanmar township code strings                                        |
| `Nrc::townships(12)`               | Bilingual township pairs: `en`, `mm`                                 |
| `Nrc::types('mm')`                 | Supported type options                                               |

`Nrc::types()` returns `en`, `mm` and a localized `label`. State number 9 has
separate Naypyidaw and Mandalay records. Parsing uses known township codes to
choose the name; selectors combine both names into one state-number option.

Conversion and building from parts throw on invalid input. Use `isValid()` or
`validate()` when handling user input.

See the [quick start](quick-start.md), [validation](guides/validation.md), [Blade selector](guides/blade-selector.md), and [custom data](guides/custom-data.md). For detailed behavior and errors, see the [shared behavioral contract](../CONTRACT.md).
