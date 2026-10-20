<?php
require_once __DIR__ . '/../inc/DefinitionParser.php';
use CalculatorPlugin\DefinitionParser;
$count = 0;
function check($ok) { global $count; if (!$ok) throw new Exception('Assertion failed'); $count++; }
$text = file_get_contents(__DIR__ . '/fixtures/definitions.txt');
$catalog = DefinitionParser::catalog($text);
$def = DefinitionParser::parse('recipe', $catalog['recipe']);
check($def['title'] === 'Scale recipe quantities');
check(count($def['inputs']) === 3);
check($def['inputs'][0]['default'] === 2.0);
check($def['outputs'][0]['decimals'] === 3);
file_put_contents(__DIR__ . '/definition.json', json_encode($def, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
foreach ([
    str_replace('decimals = 3', 'decimals = 13', $catalog['recipe']),
    str_replace('type = decimal-list', 'type = regex', $catalog['recipe']),
    str_replace('min = 1', "min = 3", $catalog['recipe']),
    str_replace('source = quantities', 'source = original_servings', $catalog['recipe']),
    str_replace('formula = value * target_servings / original_servings', 'formula = alert("test")', $catalog['recipe']),
    str_replace('title = Scale recipe quantities', "title = X\ntitle = Y", $catalog['recipe']),
    str_replace('  decimals = 3', '  unknown = 3', $catalog['recipe']),
    str_replace('input quantities:', 'input value:', $catalog['recipe']),
    str_replace('default = 2', 'default = 1.000,5', $catalog['recipe']),
    str_replace('input target_servings:', 'input original_servings:', $catalog['recipe'])
] as $bad) {
    try { DefinitionParser::parse('recipe', $bad); throw new Exception('Invalid definition accepted'); }
    catch (InvalidArgumentException $e) { $count++; }
}
try { DefinitionParser::catalog($text . $text); throw new Exception('Duplicate accepted'); }
catch (InvalidArgumentException $e) { $count++; }
echo $count . " PHP parser checks passed.\n";
