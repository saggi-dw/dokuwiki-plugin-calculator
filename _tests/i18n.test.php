<?php
require_once __DIR__ . '/../inc/DefinitionParser.php';
use CalculatorPlugin\DefinitionParser;
use CalculatorPlugin\DefinitionException;
$count = 0;
$arguments = [
    'definition_invalid_boolean' => ['break'],
    'definition_invalid_default_text' => [], 'definition_default_too_large' => [200000, 10000],
    'definition_circular_reference' => ['a -> b -> a'],
    'regex_invalid_pattern' => [], 'regex_invalid_flags' => [], 'regex_named_group_required' => [],
    'constants_multiple_blocks' => [], 'constants_invalid_line' => [],
    'constants_duplicate_name' => ['test'], 'constants_too_many' => [128],
    'constants_value_required' => ['factor'],
    'invalid_name' => [], 'definition_expression_syntax' => [],
    'definition_unknown_variable' => ['test'], 'definition_unknown_function' => ['fn'],
    'definition_function_arguments' => ['pow', 2, 2],
    'definition_invalid_output_type' => [], 'definition_scalar_source' => [],
    'definition_page_too_large' => [], 'definition_duplicate_name' => ['recipe'],
    'definition_too_many' => [64], 'definition_duplicate_title' => [],
    'definition_reserved_field' => ['value'], 'definition_duplicate_property' => ['label'],
    'definition_invalid_line' => [], 'definition_invalid_title' => [200],
    'definition_too_many_fields' => [40], 'definition_unknown_property' => ['quantities'],
    'definition_invalid_label' => ['quantities', 200], 'definition_unit_too_long' => [40],
    'definition_invalid_type' => [], 'definition_invalid_range' => [],
    'definition_default_out_of_range' => [], 'definition_invalid_formula' => [512],
    'definition_formula_character' => [], 'definition_invalid_decimals' => [0, 12],
    'definition_invalid_structure' => [1, 8, 32], 'definition_invalid_source' => [],
    'definition_invalid_number' => ['abc'], 'definition_number_too_large' => []
];
foreach (['de', 'en'] as $language) {
    $lang = [];
    require __DIR__ . '/../lang/' . $language . '/lang.php';
    foreach ($arguments as $key => $args) {
        if (!isset($lang[$key])) throw new Exception('Missing key: ' . $key);
        $message = vsprintf($lang[$key], $args);
        if (!$message || $message === $key) throw new Exception('Missing translation: ' . $key);
        $wrapped = sprintf($lang['definition_line'], 3, $message);
        if (strpos($wrapped, '3') === false || strpos($wrapped, $message) === false) throw new Exception('Invalid line wrapper');
        $count++;
    }
}
$source = file_get_contents(__DIR__ . '/../inc/DefinitionParser.php');
preg_match_all('/(?:new DefinitionException\(|self::fail\(\$index, )\x27([a-z_]+)\x27/', $source, $keys);
foreach (array_unique($keys[1]) as $key) {
    if (!array_key_exists($key, $arguments)) throw new Exception('Untested error: ' . $key);
    $count++;
}
try {
    DefinitionParser::parse('test', "\ntitle = X\ntitle = Y");
    throw new Exception('Expected parser error');
} catch (DefinitionException $e) {
    if ($e->getLanguageKey() !== 'definition_duplicate_title' || $e->getDefinitionLine() !== 3 || $e->getParameters() !== []) throw new Exception('Invalid structured error');
    $count++;
}
try {
    DefinitionParser::catalog('<calcdefinition test></calcdefinition><calcdefinition test></calcdefinition>');
    throw new Exception('Expected duplicate error');
} catch (DefinitionException $e) {
    if ($e->getParameters() !== ['test'] || $e->getDefinitionLine() !== null) throw new Exception('Invalid parameter payload');
    $count++;
}
echo $count . " internationalization checks passed.\n";
