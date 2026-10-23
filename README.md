# Calculator for DokuWiki

**English** | [Deutsch](README_DE.md)

A calculator toolkit for your wiki: define input fields and formulas on a wiki page, then embed the calculators wherever you need them.

The plugin supports individual numbers, numeric lists and numbers embedded in text. A built-in chemistry calculator also calculates molar masses and elemental mass fractions from chemical formulas.

**Documented version:** 0.6.4 · **Authors:** saggi and ChatGPT · **License:** [GNU GPL v2](LICENSE)

## Contents

- [Features](#features)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Definitions and fields](#definitions-and-fields)
- [Formulas and aggregates](#formulas-and-aggregates)
- [Constants](#constants)
- [Regex text lists](#regex-text-lists)
- [Chemistry calculator](#chemistry-calculator)
- [Configuration and access control](#configuration-and-access-control)
- [Examples](#examples)
- [Limits and error handling](#limits-and-error-handling)
- [Development and tests](#development-and-tests)

## Features

| Feature | Description |
|---|---|
| Custom calculators | Define inputs and formulas in wiki syntax, for example for unit conversions, quantity scaling, list statistics or price calculations. |
| Numeric lists | Calculate each row individually while preserving its position. |
| Regex text lists | Extract named numeric groups per row and calculate results, optionally replacing a captured value in the original text. |
| Aggregates | Use the sum, count, average, minimum and maximum of a list. |
| Linked outputs | Reference calculated scalar values and output lists in other formulas. |
| Constants | Define fixed values locally for one calculator or globally on the definitions page. |
| Formatting | Configure units, decimal places, default values and field layout. |
| Transparency | Optionally display formulas, constants and regex rules. |
| Chemistry | Display chemical notation and calculate molar masses and elemental mass fractions. |

Calculations run in the browser. Multiple calculators on the same page work independently. No external JavaScript libraries or online services are required.

## Installation

1. Copy the plugin directory to `lib/plugins/calculator/`.
2. Under **Admin → Configuration → calculator**, select the definitions page. The default is `calculator:definitions`.
3. Create that wiki page and add a calculator definition as shown in the quick start.

When updating, include all subdirectories. If the original plugin scaffold is still installed, remove its empty `syntax.php` file; syntax components now reside in `syntax/`.

The plugin does not create wiki pages or modify existing ones.

### Requirements

- DokuWiki with namespaced Extension classes and **PHP 7.4 or later**.
- A browser with modern JavaScript support. Regex lists additionally require Web Workers and RegExp match indices.
- If a Content Security Policy is used, it must allow `worker-src 'self'` for regex lists.

Integration testing has been performed with DokuWiki **2026-07-14c “Mort”** and **PHP 8.3**. See [TESTING.md](TESTING.md) for details of the test history.

## Quick start

This example calculates a final concentration from the initial concentration and the fraction of the initial solution in the final mixture.

**1. Add this definition to `calculator:definitions`:**

```text
<calcdefinition dilution>
title = Dilution

input concentration:
  type = decimal
  label = Initial concentration
  unit = %
  default = 40
  min = 0
  max = 100

input addition:
  type = decimal
  label = Fraction of the initial solution in the final mixture
  unit = %
  default = 2
  min = 0
  max = 100

output result:
  label = Final concentration
  unit = %
  formula = concentration * addition / 100
  decimals = 3
  fixed = true
</calcdefinition>
```

Paste the block as ordinary wiki source, **without wrapping it in an additional `<code>` block**. For valid definitions, the definitions page displays only the title. Errors appear as localized messages, including the line within the block where it can be identified.

**2. Embed the calculator on any wiki page:**

```text
<calcuser dilution>
```

With the default values of `40 %` and `2 %`, the result is **0.800 %** in an English-language wiki. Changing an input triggers automatic recalculation. **Calculate** runs it immediately; the copy button copies the displayed result.

## Definitions and fields

The central definitions page can contain several named `<calcdefinition name>` blocks.

### Syntax rules

- Definition names start with a lowercase letter, followed by lowercase letters, digits, `_` or `-`; maximum length: 64 characters.
- Field names follow the same rules but cannot contain hyphens. Names must be unique within a calculator; `value` is reserved.
- `title`, `showwork`, `input` and `output` start at the beginning of a line. Field properties are indented.
- Empty lines and separate comment lines starting with `#` are ignored outside quoted default text.
- Titles and labels are plain text.
- A calculator requires at least one output and supports at most **one list input**. Scalar inputs and list outputs can be combined.

### Input types

| `type` | Purpose | Properties in addition to `type` |
|---|---|---|
| `decimal` | A single numeric value | `label`; optional `unit`, `default`, `min`, `max`, `break` |
| `decimal-list` | One number per row | `label`; optional `unit`, `default`, `min`, `max`, `break` |
| `regex-list` | Extract named numbers from text rows | `label`, `pattern`; optional `flags`, `unit`, `default`, `min`, `max`, `break` |
| `const` | A fixed local constant | `value`; optional `label`, `unit`; no input control |

`min` and `max` are inclusive. For lists, they apply to each recognized number. Scalar numeric inputs cannot be empty; empty lists are allowed.

### Outputs

| Property | Meaning |
|---|---|
| `label` | Display label; required. |
| `formula` | Calculation expression; required. |
| `type` | `decimal`, `decimal-list` or `text-list`; see below. |
| `source` | Name of the list input used by a list output. |
| `unit` | Optional display unit; does not perform automatic unit conversion. |
| `decimals` | Maximum displayed decimal places: `0` to `12`; default: `3`. |
| `fixed` | Set to `true` to display exactly `decimals` places; default: `false`. |
| `template` | Optional plain-text output template; see below. |
| `break` | Set to `true` to start a new row and use the full width; default: `false`. |

Without `type`, a scalar output is assumed. If `source` is specified, the output type is inferred from the input: `decimal-list` for a numeric list or a regex list without a `value` group; `text-list` for a regex list with a `value` group. A regex source can also explicitly produce a `decimal-list`.

In numeric list outputs, `value` is the current number from the input. Regex sources provide `row.name` for named captures and also `value` when that group exists. Scalar outputs have neither `source` nor `value`. `source` always refers to an **input**, not another output.

### Output templates

An optional `template` controls the displayed text for each result. Place it in the output block, without surrounding quotes:

```text
  template = {line} → {label}: {value} {unit}
```

For example:

```text
1.25 kg x 2 units @ 8.00 EUR/kg → Price per row: 20.00 EUR
```

| Placeholder | Replaced with |
|---|---|
| `{line}` | The original input line, including its spacing; list outputs only. |
| `{label}` | The output label. |
| `{value}` | The calculated number, formatted using the wiki locale, `decimals` and `fixed`. |
| `{unit}` | The output unit, or an empty string if none is defined. |

Placeholders can appear in any order, for example `{value} {unit} ← {line}`. Templates also work for scalar outputs, where `{line}` is unavailable. Empty and unmatched input lines remain unchanged. Templates affect only display; aggregates and other outputs still use unrounded numeric results.

Without `template`, existing output behavior is preserved, including replacing the `value` capture in text lists. A template overrides that replacement with the whole formatted line. Templates are plain text, not HTML or expressions. Use one non-empty line of at most 2000 UTF-8 bytes; only the four placeholders are supported, with no literal braces or escaping. See [templated_output.txt](examples/templated_output.txt) for a complete definition.

### Defaults and layout

Lists can be prefilled with multiline text:

```text
input quantities:
  type = decimal-list
  label = Quantities
  default = "0.500
0.060
0.040"
  min = 0
  break = true
```

Continuation lines do not need indentation. Spaces, tabs, empty lines and lines starting with `#` inside the text are preserved; line endings are normalized to LF. Only whitespace may follow the closing quote.

Inside quoted default text, `\"` represents a double quote and `\\` a backslash. Other backslash sequences remain literal: enter an actual line break rather than `\n`.

Numeric list defaults are checked for valid numbers and range limits when the definition is validated. Regex default text is processed like ordinary user input during calculation.

Set `showwork = true` at definition level to display the **Calculation basis**: formulas, referenced constants and regex rules. The default is `false`. This setting affects presentation; the definition is still sent to the browser for calculation.

## Formulas and aggregates

### Numbers and operators

Numeric inputs accept decimal commas or decimal points, but not thousands separators, units, fractions or scientific notation. **`1.000` means `1`, not one thousand.**

In formulas, always use a **decimal point**. Commas separate function arguments.

| Expression | Meaning |
|---|---|
| `+`, `-`, `*`, `/` | Basic arithmetic |
| `%` | Division remainder, not a percentage sign |
| `^` | Exponentiation |
| `( … )`, `+x`, `-x` | Parentheses and unary signs |
| `abs(x)`, `sqrt(x)` | Absolute value and square root |
| `round(x)`, `floor(x)`, `ceil(x)` | Round to an integer, round down and round up |
| `pow(x, y)` | Power function |
| `min(x, y, …)`, `max(x, y, …)` | Minimum or maximum of 2 to 16 scalar arguments |

There is no implicit multiplication: write `2 * amount`, not `2amount`. Exponentiation is right-associative: `2^3^2 = 512`; `-2^2 = -4`. `round()` follows JavaScript `Math.round()`, for example `round(-1.5) = -1`.

### Conditions and optional captures

`exists(row.name)` tests whether a named regex group captured text in the current row. It returns `1` when present and `0` when an alternative or optional group did not participate. A captured zero **is present**. An empty captured string is also present, but is not a valid number when used in arithmetic. The argument must be a known `row.name` from the output's regex source; arbitrary expressions, scalar inputs and lists are not accepted.

`if(condition, then, else)` evaluates the condition followed by **only the selected branch**. Zero selects `else`; any other finite number selects `then`. An empty result (`null`), for example `avg()` of an empty list, remains empty without evaluating either branch. Calls can be nested within the existing expression size and nesting limits.

```text
  formula = if(exists(row.component), row.component * factor, 0)
```

For several alternative row types:

```text
  formula = if(exists(row.item), row.item, if(exists(row.discount), -row.discount, row.fee))
```

Missing groups are still errors if read directly. Named groups are converted to numbers and checked against the input's range only when read; `exists()` checks presence, not numeric validity. Ordinary numeric inputs and the legacy `value` capture continue to be validated as before. Empty and unmatched lines are skipped as before.

All branches are checked for syntax, valid names and output dependencies when loading the definition. Output dependencies remain static: `if()` does not bypass an invalid definition, a circular reference or errors in another output. It skips evaluation only inside its own unselected branch. See [conditional_rows.txt](examples/conditional_rows.txt) for a complete calculator with nested conditions, templates and an aggregate total.

### List functions

| Function | Result | Empty list |
|---|---|---|
| `sum(quantities)` | Sum | `0` |
| `count(quantities)` | Number of numeric values | `0` |
| `avg(quantities)` | Arithmetic mean | Empty |
| `min(quantities)` | Smallest value | Empty |
| `max(quantities)` | Largest value | Empty |

Here, `quantities` names a list input or a calculated output list. Empty rows and regex rows without a number are ignored; numeric zero is included. Missing results remain empty in further expressions, such as `avg(quantities) + 1`.

Each aggregate accepts one list name. Expressions such as `sum(quantities * 2)`, `sum(1, 2)` and `avg(1, 2)` are not supported.

### Referencing outputs

Reference scalar outputs directly by name and output lists through an aggregate function:

```text
output net_amounts:
  type = decimal-list
  source = quantities
  label = Net amounts
  formula = value * price
  decimals = 2
  fixed = true

output tax:
  label = Tax amount
  formula = sum(net_amounts) * global.tax_rate
  decimals = 2
  fixed = true
```

This fragment requires the inputs `quantities` and `price` and a global constant named `tax_rate`. Outputs may appear in any order: dependencies are resolved automatically, and circular references are reported as errors. Display order still follows the definition.

### Display formatting and calculation values

`decimals` and `fixed` affect only the display. Further calculations use **unrounded results**; for text lists, they use the underlying numeric values. The general calculator does not use thousands separators.

To round the calculated value itself to three decimal places, include the rounding in the formula:

```text
formula = round(value * factor * 1000) / 1000
```

A subsequent sum then uses those rounded values. JavaScript uses floating-point numbers, not exact decimal arithmetic.

## Constants

**Local constants** belong to one calculator definition and do not create input controls:

```text
input factor:
  type = const
  label = Conversion factor
  value = 10000
```

A formula can then use `amount * factor`. `value` is required; if `label` is omitted, the name is used as the label. Constants do not support `default`, `min`, `max` or `break`.

**Global constants** are declared in a shared block on the central definitions page:

```text
<calcconstants>
ppm_per_percent = 10000
grams_per_kg = 1000
tax_rate = 0.19
</calcconstants>
```

Reference them using names such as `global.ppm_per_percent`. The prefix is part of the permitted name, not a general object access mechanism. One constants block with up to 128 entries is allowed per page. Values must be numbers, not formulas or references to other constants.

Names start with a lowercase letter, followed by lowercase letters, digits or `_`; maximum length: 64 characters. Local and global constants may share the same name. With `showwork = true`, only constants actually referenced by the calculator are displayed.

## Regex text lists

A `regex-list` input recognizes named numeric groups in each row. With a group named `value`, a `text-list` output replaces that capture while preserving the rest of the text. Without `value`, use a `decimal-list` output to calculate a separate result per matched row.

```text
<calcdefinition scale_text>
title = Scale quantities in text
showwork = true

input factor:
  type = decimal
  label = Scaling factor
  default = 2
  min = 0

input quantities:
  type = regex-list
  label = One quantity at the start of each row
  default = "100 g flour
250 ml milk
2 eggs
Salt to taste"
  pattern = ^\s*(?:[•*]\s*|-\s+)?(?<value>[+-]?(?:\d+(?:[.,]\d+)?|[.,]\d+))
  flags = u
  min = 0
  break = true

output scaled:
  type = text-list
  source = quantities
  label = Scaled text
  formula = value * factor
  decimals = 3
  break = true
</calcdefinition>
```

Embed it with `<calcuser scale_text>`. With a factor of `2`, `100 g flour` becomes `200 g flour`; `Salt to taste` remains unchanged.

- A pattern needs at least one named group. **`value`** is the replacement target for `text-list` output.
- `pattern` contains the raw regex without enclosing `/` delimiters. Write backslashes directly, for example `\d`.
- `flags` supports `i` and `u`, each at most once; the default is `u`.
- Only the first match per row is processed. Empty rows and unmatched rows remain unchanged.
- Additional numbers, units and other text outside the `value` group are preserved.
- An invalid numeric capture or a value outside `min`/`max` produces an error with the row number.

Definition validation uses PHP-PCRE; calculations use JavaScript-RegExp. Patterns should therefore be compatible with both. Matching runs in a Web Worker with a **two-second time limit**; new input cancels any pending evaluation.

### Multiple numeric groups per row

Use `row.quantity`, `row.count` and `row.price` to access groups declared as `(?<quantity>...)`, `(?<count>...)` and `(?<price>...)`. For example:

```text
input items:
  type = regex-list
  label = Quantity, count and price per row
  pattern = ^(?<quantity>\d+(?:[.,]\d+)?)\s*kg\s*x\s*(?<count>\d+)\s*units\s*@\s*(?<price>\d+(?:[.,]\d+)?)\s*EUR/kg$
  flags = u

output line_totals:
  type = decimal-list
  source = items
  label = Price per row
  formula = row.quantity * row.count * row.price
  decimals = 2
  fixed = true
```

The row `2.5 kg x 3 units @ 4.20 EUR/kg` produces `31.50` in an English-language wiki. A scalar output can then use `sum(line_totals)`. See [multi_value_text.txt](examples/multi_value_text.txt) for a complete calculator with default inputs and a total.

Group names start with a lowercase letter and use lowercase letters, digits and underscores. A group is available only within a list formula using its regex input as `source`. It does not conflict with scalar input names: `row.price` and `price` are distinct.

- Referenced groups must contain valid decimal numbers. Optional groups missing from a matched row cause an error if the formula uses them.
- Input `min` and `max` apply to the numeric groups used by formulas. A legacy `value` group is always validated.
- Unmatched and empty rows produce blank result rows. An entered zero is a numeric value.
- Unused text captures remain allowed, preserving compatibility with existing patterns.
- Without a `value` group, aggregate the calculated output list, such as `sum(line_totals)` or `count(line_totals)`; the input has no single numeric column to aggregate.
- A `text-list` output requires `value` and replaces only that group. Selecting other replacement targets is not supported.
- Existing formulas using `value` remain valid; `row.value` is also available.

## Chemistry calculator

The chemistry calculator does not need a definition on the central wiki page.

| Wiki syntax | Behavior |
|---|---|
| `<calcchem>` | Formula and proportion inputs with immediate calculation. An empty proportion means 100 %; the permitted range is 0 to 100 %. |
| `<calcchem H2O>` | Fixed formula, formatted notation and results table without input fields; proportion: 100 %. |

The output shows molar mass, element symbols, atom counts, atomic masses and mass fractions in percent and ppm. Indices are displayed as subscripts, for example **H₂O** and **CuSO₄·5H₂O**.

Percentages are displayed with three decimal places and ppm as integers, using locale-specific thousands separators. Double-clicking a numeric cell, or pressing Enter while it is focused, copies the unrounded value. The table copy button copies the displayed values as tab-separated text.

### Supported formulas

- Case-sensitive element symbols: `H2O`, not `h2o`.
- Positive integer indices and coefficients, and repeated element symbols.
- Nested parentheses, square brackets and braces.
- Hydrates and components separated by `·`, `*` or `.`.
- Subscript digits as input, for example `H₂O`.

Compound names, CAS searches, charges, isotope notation, fractional/decimal indices, variable groups and alternative occupancies such as `(Mg,Fe)` are not supported. Whitespace inside a formula is not removed automatically. A period is always a component separator, never a decimal point.

Elemental fractions refer to the pure compound or its specified proportion in a mixture. Their sum equals that proportion; molar mass and atom counts remain unchanged. Leading coefficients multiply the entire formula unit: for `2H2O`, the reported mass refers to both units.

The calculator contains 84 central values from the [CIAAW Abridged Standard Atomic Weights 2024](https://www.ciaaw.org/abridged-atomic-weights.htm). Elements without a stored value are rejected; uncertainties and isotope variations are not propagated. Fixed formulas are also calculated in the browser and require JavaScript.

### Formulas from Struct page data

If the optional [Struct plugin](https://www.dokuwiki.org/plugin:struct) is installed, a fixed chemistry calculator can read a single text field from the current page:

```text
<calcchem {{$material.formula}}>
```

Here `material` is the schema and `formula` the field label. The field must contain one plain chemical formula, for example `C32H16CuN8`. Use the exact schema and field names. The whole formula argument must be a Struct reference. Calculator loads Struct only for such references and delegates their inner syntax to Struct's own `InlineConfigParser`. Spaces and quoted field names are accepted, for example `<calcchem {{$ material.formula }}>` and `<calcchem {{$material."Chemical formula"}}>`. References to other pages, additional filters, schema aliases, multiple columns, global/serial data and mixed literal/reference formulas are not supported. Unsupported selections produce a localized message; they are never silently treated as a current-page lookup.

Calculator reads the raw value through Struct's public helper API while rendering the page, after checking read access to the current page. It does not render Struct markup inside the tag. The value is not stored in parser instructions. For a page containing this reference, Calculator disables the rendered page cache so saved Struct changes appear on the next page load. Existing revision views pass their revision timestamp to Struct. Unsaved Struct edits are not read.

An unavailable Struct plugin, a missing/empty field, a non-text or multi-value field and read failures produce localized messages. An empty field does not switch to an interactive calculator. Literal `<calcchem TiO2>` and interactive `<calcchem>` work without Struct and retain their normal caching behavior.

## Configuration and access control

Two settings are available under **Admin → Configuration → calculator**:

| Setting | Default | Meaning |
|---|---|---|
| `definitions_page` | `calculator:definitions` | Full page ID of the central definitions page. |
| `require_definition_read` | Enabled | Readers need access to the definitions page in addition to the page containing the calculator. |

If `require_definition_read` is disabled, readers of the calculator page may use its definition without permission to read the source page. The definition is sent to their browser and must therefore not contain confidential information.

Definitions are fetched through DokuWiki's AJAX endpoint whenever a calculator page is loaded. Responses are not cached, and permissions are checked on every request. The wiki page cache contains only the placeholder. Reload open calculator pages after changing definitions or configuration.

Showing only titles on the definitions page and setting `showwork = false` do not hide source code: editing views, page source and revision history remain subject to normal wiki permissions. The chemistry calculator does not fetch a definition.

## Examples

The [examples directory](examples/README.md) contains self-contained calculators with English labels and generic sample data. Number formatting follows the language of the wiki.

| File | Example |
|---|---|
| [unit_conversion.txt](examples/unit_conversion.txt) | Convert kilograms to grams using a local constant. |
| [percentage_conversion.txt](examples/percentage_conversion.txt) | Convert percentages to ppm and back. |
| [scale_quantities.txt](examples/scale_quantities.txt) | Scale a list to a different number of servings or units. |
| [scale_text.txt](examples/scale_text.txt) | Scale quantities in recipe text and preserve unmatched rows. |
| [multi_value_text.txt](examples/multi_value_text.txt) | Calculate a row price from quantity, count and price captures. |
| [statistics.txt](examples/statistics.txt) | Calculate sum, count, average, minimum and maximum. |
| [price_calculation.txt](examples/price_calculation.txt) | Calculate net amounts, tax and gross total with linked outputs. |
| [dilution.txt](examples/dilution.txt) | Calculate a final concentration from two percentage inputs. |
| [chemistry.txt](examples/chemistry.txt) | Explore water, sodium chloride, glucose and grouped or hydrated formulas. |

The examples index includes embedding tags and expected default results. Use a common unit when summing numeric quantities. The text example preserves units without converting them. The price example uses one shared unit price and an illustrative tax rate.

Copy only the definitions you need. Merge global constants into an existing `<calcconstants>` block if present, and avoid duplicate definition names.

## Limits and error handling

| Area | Limit |
|---|---|
| Calculator definition | 32 inputs, 8 outputs, at most one list input |
| Definitions page | 64 definitions, 1 MiB of source text |
| Global constants | 128 entries |
| Formula | 512 characters, 32 nesting levels |
| List input | 10,000 rows, 200,000 characters |
| Default list text | 10,000 rows, 200,000 UTF-8 bytes |
| Regex pattern | 512 bytes |
| Chemical formula | 2,000 characters, 32 bracket nesting levels, safe integer atom counts |

The definitions page validates syntax, properties, types, sources, defaults and formula dependencies, including during preview. A valid definition does not guarantee a valid result for every input: division by zero and non-finite results are detected during calculation.

Calculation errors clear previous results and disable copy buttons. List errors identify the affected row. Rounding may cause the sum of displayed values to differ slightly from the displayed total.

Copying uses the Clipboard API where available. Otherwise, the plugin attempts a fallback; if copying is still unavailable, the selected text can be copied manually.

## Development and tests

PHP reads and validates definitions; JavaScript performs the calculations. The expression parser uses neither `eval()` nor `Function()`.

| Directory / file | Purpose |
|---|---|
| `syntax/` | Wiki syntax for calculators, definitions and constants |
| `inc/` | Definition parser and server-side expression validation |
| `modules/` | Calculation engines, user interfaces and regex worker |
| `action.php`, `helper.php` | AJAX, page access, permissions and cache integration |
| `script.js`, `style.css` | Initialization and presentation |
| `lang/en/`, `lang/de/` | English and German translations |
| `_tests/` | PHP/JavaScript tests and English test fixtures |

### Running tests

From the plugin directory, run the PHP tests first: they also generate the JSON fixtures used by the JavaScript tests. Then run the JavaScript tests. For example, in a POSIX shell:

```sh
for test in _tests/*.test.php; do
  php "$test" || exit 1
done

node --test _tests/*.test.js
```

Tests cover expressions, aggregates, output references, defaults, rounding, internationalization and chemistry. Individual tests can be run directly with `php` or `node --test`. See [TESTING.md](TESTING.md) for details and previous integration tests.

### Translations and extensions

English is the base language; German is included. Additional translations can be added under `lang/<language>/`. Titles and field labels remain in the language chosen by the definition author.

The PHP parser reports language keys, parameters and an optional line number. DokuWiki translates these through `getLang()`. Additional programmed calculators can register modules under `DokuCalculator.modules` and be integrated through a syntax component; scripts are registered through `JS_SCRIPT_LIST` in `action.php`. A module installation mechanism is not included.

The [`.gitattributes`](.gitattributes) excludes dotfiles, `_tests/`, `examples/` and `TESTING.md` from `git archive`. These files still belong in the source repository; installation archives created this way do not contain them.

## License

GNU General Public License, version 2. See [LICENSE](LICENSE).

Authors: **saggi and ChatGPT** · Contact: <saggi@gmx.de>
