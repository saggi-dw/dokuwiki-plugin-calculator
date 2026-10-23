<?php
require_once __DIR__.'/../inc/DefinitionParser.php';
use CalculatorPlugin\DefinitionParser;
use CalculatorPlugin\ExpressionValidator;
use CalculatorPlugin\DefinitionException;
$catalog = DefinitionParser::catalog(file_get_contents(__DIR__.'/../examples/conditional_rows.txt'));
$body = $catalog['conditional_charges'];
$definition = DefinitionParser::parse('conditional_charges', $body);
$variables = ['row.item', 'row.discount', 'row.fee', 'factor'];
$cases = [
    ['exists(row.item)', true],
    ['if(exists(row.item), row.item, 0)', true],
    ['if(exists(row.item), row.item, if(exists(row.discount), -row.discount, row.fee))', true],
    ['if(0, 1/0, 3)', true],
    ['if(-1, 2, 3)', true],
    ['if(exists(row.item), row.item * factor, 0)', true],
    ['exists(row.unknown)', false],
    ['exists(factor)', false],
    ['exists(1)', false],
    ['exists(row.item + 1)', false],
    ['exists()', false],
    ['exists(row.item,row.fee)', false],
    ['exists(sum(charges))', false],
    ['if(1,2)', false],
    ['if(1,2,3,4)', false],
    ['if()', false],
    ['if(1,2,unknown)', false],
    ['if(1,2,evil())', false],
    ['if(exists(row.item), row.item, row.item.constructor)', false],
    [str_repeat('if(1,', 40).'1'.str_repeat(',0)',40), false],
];
$count = 0;
foreach ($cases as [$expression, $expected]) {
    $accepted = true;
    try { ExpressionValidator::validate($expression, $variables, ['charges']); }
    catch (DefinitionException $e) { $accepted = false; }
    if ($accepted !== $expected) throw new Exception('Conditional expression mismatch: '.$expression);
    $count++;
}
$refs = ExpressionValidator::validate('if(exists(row.item), row.item, sum(charges))', $variables, ['charges']);
if ($refs !== ['row.item', 'charges']) throw new Exception('Conditional references were lost');
$count++;
foreach (['if(exists(row.unknown), 0, 0)', 'if(1, 0, unknown)', 'exists(1)'] as $formula) {
    try {
        DefinitionParser::parse('conditional_charges', preg_replace('/  formula = if[^\n]+/', '  formula = '.$formula, $body));
        throw new Exception('Invalid conditional definition accepted');
    } catch (DefinitionException $e) {
        if ($e->getDefinitionLine() !== 20) throw $e;
        $count++;
    }
}
try {
    DefinitionParser::parse('conditional_charges', preg_replace('/  formula = if[^\n]+/', '  formula = if(0, total, 0)', $body));
    throw new Exception('Circular reference in an unselected branch was accepted');
} catch (DefinitionException $e) {
    if ($e->getLanguageKey() !== 'definition_circular_reference') throw $e;
    $count++;
}
file_put_contents(__DIR__.'/conditions.json', json_encode(['definition'=>$definition, 'variables'=>$variables, 'cases'=>$cases], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n");
echo "$count conditional parser checks passed.\n";
