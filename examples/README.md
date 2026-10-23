# Example calculators

These examples demonstrate general plugin features. Definitions and labels are in English; number formatting follows the language of the wiki.

## Using an example

1. Copy the selected `.txt` file into the configured definitions page (default: `calculator:definitions`) as wiki source, without an extra `<code>` block.
2. Add its `<calcuser name>` tag to the page where you want to use it.
3. Adjust input values or edit the definition to suit your task.

The definitions use unique names and can share a definitions page. Avoid adding the same definition twice, for example when it is already present from the README quick start. A page may contain only one `<calcconstants>` block: merge constants into an existing block rather than adding another one.

| File | Embed | Demonstrates |
|---|---|---|
| [unit_conversion.txt](unit_conversion.txt) | `<calcuser unit_conversion>` | Scalar input, a local constant and unit conversion. |
| [percentage_conversion.txt](percentage_conversion.txt) | `<calcuser percent_to_ppm>` or `<calcuser ppm_to_percent>` | Two independent conversion calculators. |
| [scale_quantities.txt](scale_quantities.txt) | `<calcuser scale_quantities>` | Scaling a numeric list, summing an output list and counting inputs. |
| [scale_text.txt](scale_text.txt) | `<calcuser scale_text>` | Replacing captured quantities while preserving text and units. |
| [multi_value_text.txt](multi_value_text.txt) | `<calcuser line_prices>` | Several numeric captures per row, scoped `row.name` access and summing the results. |
| [templated_output.txt](templated_output.txt) | `<calcuser formatted_line_prices>` | Original input lines followed by labels, formatted prices and units. |
| [conditional_rows.txt](conditional_rows.txt) | `<calcuser conditional_charges>` | Nested conditions for alternative item, discount and fee rows. |
| [statistics.txt](statistics.txt) | `<calcuser statistics>` | Sum, count, average, minimum and maximum. |
| [price_calculation.txt](price_calculation.txt) | `<calcuser price_calculation>` | A global constant and linked scalar/list outputs. |
| [dilution.txt](dilution.txt) | `<calcuser dilution>` | Two scalar percentage inputs with range limits. |
| [chemistry.txt](chemistry.txt) | Paste directly into any wiki page | Interactive and fixed chemical formulas; no central definition needed. |

## Default results

| Calculator | Default input | Expected result |
|---|---|---|
| Unit conversion | 1.5 kg | 1500.000 g |
| Percent to ppm | 0.25 % | 2500 ppm |
| ppm to percent | 2500 ppm | 0.25 % |
| Scale quantities | 100, 250 and 50; scale from 2 to 5 | 250, 625 and 125; total 1000; count 3 |
| Scale text | Factor 2 | 200 g flour, 500 ml milk, 4 eggs; “Salt to taste” unchanged |
| Multiple values | 2.5 kg x 3 units @ 4.20 EUR/kg; 1.25 kg x 2 units @ 8.00 EUR/kg | Row prices 31.50 and 20.00; total 51.50; matched rows 2 |
| Statistics | 12.5, 15, 17.5, an empty row and 0 | Sum 45; count 4; average 11.25; minimum 0; maximum 17.5 |
| Price calculation | Quantities 2, 3 and 1; unit price EUR 12.50; tax rate 0.19 | Net EUR 75.00; tax EUR 14.25; gross EUR 89.25 |
| Dilution | 40 % initial concentration; 2 % of the final mixture | 0.800 % final concentration |

Decimals and trailing zeros depend on the definition and wiki language. The table uses English number formatting.

## Notes

- The quantity-scaling example uses a common unit for all numeric rows. Its total is meaningful only when the input values are comparable.
- The text-scaling example preserves units but does not convert them or match items by name.
- The price example applies the same unit price to all quantities. The tax rate is illustrative. Two displayed decimal places do not implement invoice-specific rounding rules.
- The dilution input describes a fraction of the final mixture, not an additive dose relative to a starting quantity.
- Empty rows do not count as measurements; an entered zero does. With no measurements, `sum()` and `count()` return 0, while `avg()`, `min()` and `max()` remain empty.

See the main [English README](../README.md) or [German README](../README_DE.md) for the full syntax reference.

The multiple-value example returns a numeric list. Only patterns with a named `value` group support replacing a number in the original text. To retain the whole original line as a prefix or suffix, use an output `template`, as shown in the templated-output example.

The templated-output example uses the same numbers as the multiple-value example. Its first result is `2.5 kg x 3 units @ 4.20 EUR/kg → Price per row: 31.50 EUR`; its total remains 51.50 EUR.

The conditional-charges example produces 12.50, -2.00 and 1.50 EUR, for a total of 12.00 EUR. Alternative groups are tested with `exists()` before their values are used.
