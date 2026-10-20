<?php
/** Calculator UI strings. GPL-2.0. */
$lang['locale'] = 'en-US';
$lang['loading'] = 'Loading calculator …';
$lang['load_error'] = 'Unable to load calculator.';
$lang['calculate'] = 'Calculate';
$lang['copy'] = 'Copy result';
$lang['copied'] = 'Result copied.';
$lang['copy_error'] = 'Copy failed.';
$lang['copy_select'] = 'Result selected. Please press Ctrl+C to copy.';
$lang['number_hint'] = 'Comma or dot as decimal separator; no thousands separators.';
$lang['list_hint'] = 'One number per line. Blank lines are preserved.';
$lang['invalid_number'] = 'Please enter a valid number.';
$lang['range_error'] = 'Value is outside the permitted range.';
$lang['line'] = 'Line';
$lang['division_zero'] = 'Division by zero is not permitted.';
$lang['invalid_expression'] = 'Invalid expression in the definition.';
$lang['unknown_variable'] = 'Unknown variable in the definition.';
$lang['nonfinite'] = 'Result is undefined or too large.';
$lang['empty_list'] = 'Please enter at least one number in the list.';
$lang['required'] = 'Please enter a value.';
$lang['result'] = 'Result';
$lang['too_many_lines'] = 'A maximum of 10,000 lines and 200,000 characters is allowed.';
$lang['javascript_required'] = 'JavaScript is required for this calculator.';
$lang['invalid_name'] = 'Invalid definition name.';
$lang['access_denied'] = 'You do not have permission to read the calculator or its definition page.';
$lang['missing_page'] = 'The configured definition page does not exist.';
$lang['missing_definition'] = 'Calculator definition not found:';

// Definition parser diagnostics (translated with DokuWiki getLang()).
$lang['definition_page_too_large'] = 'The definition page is too large (maximum 1 MiB).';
$lang['definition_duplicate_name'] = 'Duplicate definition name: %s';
$lang['definition_too_many'] = 'A maximum of %d definitions per page is allowed.';
$lang['definition_duplicate_title'] = 'Title specified more than once.';
$lang['definition_reserved_field'] = 'Duplicate or reserved field name: %s';
$lang['definition_duplicate_property'] = 'Property specified more than once: %s';
$lang['definition_invalid_line'] = 'Unknown or incorrectly indented definition line.';
$lang['definition_invalid_title'] = 'A title with a maximum length of %d bytes is required.';
$lang['definition_too_many_fields'] = 'A maximum of %d fields is allowed.';
$lang['definition_unknown_property'] = 'Unknown property in %s.';
$lang['definition_invalid_label'] = 'Label is missing or too long: %s (maximum %d bytes).';
$lang['definition_unit_too_long'] = 'Unit is too long (maximum %d bytes).';
$lang['definition_invalid_type'] = 'Input type must be decimal, decimal-list, regex-list or const.';
$lang['definition_invalid_range'] = 'Minimum is greater than maximum.';
$lang['definition_default_out_of_range'] = 'Default value is outside the permitted range.';
$lang['definition_invalid_formula'] = 'A valid formula is missing or too long (maximum %d bytes).';
$lang['definition_formula_character'] = 'Invalid character in the expression.';
$lang['definition_invalid_decimals'] = 'decimals must be between %d and %d.';
$lang['definition_invalid_structure'] = '%d to %d outputs are required, with a maximum of %d inputs and at most one value list.';
$lang['definition_invalid_source'] = 'The output source must be a list of the matching type.';
$lang['definition_invalid_number'] = 'Invalid numeric value: %s';
$lang['definition_number_too_large'] = 'Numeric value is too large.';
$lang['definition_line'] = 'Definition line %d: %s';

$lang['definition_invalid_output_type'] = 'Output type must be decimal, decimal-list or text-list.';
$lang['definition_scalar_source'] = 'A decimal output must not have a source property.';

$lang['definition_expression_syntax'] = 'Invalid expression: check operators, numbers and parentheses.';
$lang['definition_unknown_variable'] = 'Unknown or unavailable variable: %s';
$lang['definition_unknown_function'] = 'Unknown function: %s';
$lang['definition_function_arguments'] = 'Function %s expects %d to %d arguments.';

$lang['calculation_basis'] = 'Calculation basis';
$lang['constant_name'] = 'Constant';
$lang['constant_value'] = 'Value';
$lang['constant_scope'] = 'Scope';
$lang['scope_local'] = 'Local';
$lang['scope_global'] = 'Global';
$lang['global_constants'] = 'Global constants';
$lang['constants_multiple_blocks'] = 'Only one calcconstants block per definition page is allowed.';
$lang['constants_invalid_line'] = 'Invalid global constant; expected name = number.';
$lang['constants_duplicate_name'] = 'Global constant defined more than once: %s';
$lang['constants_too_many'] = 'A maximum of %d global constants is allowed.';
$lang['constants_value_required'] = 'The constant %s requires value.';

$lang['chem_title'] = 'Element mass fractions';
$lang['chem_formula'] = 'Chemical formula';
$lang['chem_part'] = 'Proportion (%) – blank = 100';
$lang['chem_hint'] = 'Examples: TiO2, Ca(OH)2, CuSO4·5H2O. Element symbols are case-sensitive.';
$lang['chem_molar_mass'] = 'Molar mass';
$lang['chem_total'] = 'Sum of mass fractions';
$lang['chem_rounding'] = 'Table values are rounded.';
$lang['chem_element'] = 'Element';
$lang['chem_count'] = 'Atom count';
$lang['chem_weight'] = 'Atomic mass (u)';
$lang['chem_percent'] = 'Mass fraction (%)';
$lang['chem_ppm'] = 'Mass fraction (ppm)';
$lang['chem_copy_table'] = 'Copy table';
$lang['chem_copy_cell'] = 'Double-click or press Enter to copy the unrounded numeric value.';
$lang['chem_enter_formula'] = 'Please enter a chemical formula.';
$lang['chem_part_error'] = 'Please enter a proportion between 0 and 100%.';
$lang['chem_too_long'] = 'The formula is too long (maximum 2000 characters).';
$lang['chem_invalid_count'] = 'Subscripts and coefficients must be positive whole numbers.';
$lang['chem_too_large'] = 'The numbers in the formula are too large.';
$lang['chem_too_deep'] = 'Too many nested brackets.';
$lang['chem_missing_bracket'] = 'A closing bracket is missing.';
$lang['chem_unknown_element'] = 'Unknown element or no standard atomic weight available.';
$lang['chem_invalid_character'] = 'Invalid character in the formula.';
$lang['chem_empty_group'] = 'Empty formula group or missing formula component.';
$lang['chem_position'] = 'Position';
$lang['chem_basis'] = 'M = Σ(n × atomic mass). Element mass fraction (%) = n × atomic mass / M × entered proportion. ppm = % × 10000. Molar mass is independent of the proportion. Without a proportion, the pure compound (100%) is used.';
$lang['chem_weights_source'] = 'Standard atomic weights: central values without uncertainty propagation or isotopic variation. Source:';
$lang['chem_fixed'] = 'Fixed formula';

$lang['regex_hint'] = 'The first number captured as value on each line is replaced. Text and unmatched lines are preserved.';
$lang['regex_pattern'] = 'Regular expression';
$lang['regex_processing'] = 'Processing text …';
$lang['regex_invalid_pattern'] = 'Invalid regular expression (maximum 512 bytes, without / delimiters).';
$lang['regex_invalid_flags'] = 'Allowed regular-expression flags: i and u, each at most once.';
$lang['regex_named_group_required'] = 'The regular expression requires a named numeric group (?<value>...).';
$lang['regex_invalid_capture'] = 'The regular expression matched but did not capture a number as value.';
$lang['regex_unsupported'] = 'Unable to start regular-expression processing. Web Workers and regular-expression group indices must be available.';
$lang['regex_timeout'] = 'Regular-expression processing was stopped after 2 seconds. Please simplify the pattern or input.';
$lang['regex_not_ready'] = 'The text has not been processed yet.';

$lang['definition_invalid_boolean'] = 'Property %s must be true or false.';

$lang['definition_circular_reference'] = 'Circular output reference: %s.';
$lang['circular_reference'] = 'Circular reference between outputs.';

$lang['definition_invalid_default_text'] = 'The default text must end with a double quote followed only by whitespace.';

$lang['definition_default_too_large'] = 'The default text may contain at most %s bytes and %s lines.';
