<?php
require_once __DIR__ . '/../inc/DefinitionParser.php';
use CalculatorPlugin\DefinitionParser;
use CalculatorPlugin\ExpressionValidator;
use CalculatorPlugin\DefinitionException;
$cases = [];
foreach (['0', 'value * factor / 100', '-2^2', '2^-2', '2^3^2', 'min(value, 2, 3)', 'max(abs(-value),sqrt(16))', 'pow(2,3)%2', 'round(2.5)+floor(1.1)+ceil(1.1)', '(value+factor)*.5', '1/0'] as $formula) $cases[] = [$formula, ['value','factor'], true];
foreach (['', 'value *', '(value+1', 'pow(2)', 'sqrt()', 'min(2)', 'unknown+1', 'alert(1)', 'value[0]', 'value.constructor', '1 2', '1;2', '1,5', 'foo(1)', 'value factor', str_repeat('(',40).'1'.str_repeat(')',40)] as $formula) $cases[] = [$formula, ['value','factor'], false];
$count = 0;
foreach ($cases as $case) {
    $accepted = true;
    try { ExpressionValidator::validate($case[0], $case[1]); }
    catch (DefinitionException $e) {
        $accepted = false;
        foreach (['de','en'] as $language) {
            $lang = []; require __DIR__ . '/../lang/' . $language . '/lang.php';
            if (!isset($lang[$e->getLanguageKey()])) throw new Exception('Missing translation');
            vsprintf($lang[$e->getLanguageKey()], $e->getParameters());
        }
    }
    if ($accepted !== $case[2]) throw new Exception('Wrong acceptance: ' . $case[0]);
    $count++;
}
file_put_contents(__DIR__ . '/expressions.json', json_encode($cases, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
$source = file_get_contents(__DIR__ . '/fixtures/dilution.txt');
$body = DefinitionParser::catalog($source)['dilution'];
foreach ([['initial * (', 'definition_expression_syntax'], ['unknown_input * addition', 'definition_unknown_variable'], ['pow(initial)', 'definition_function_arguments'], ['value', 'definition_unknown_variable']] as $case) {
    $bad = str_replace('initial * addition / 100', $case[0], $body);
    $line = array_search('  formula = ' . $case[0], explode("\n", $bad), true) + 1;
    try { DefinitionParser::parse('dilution', $bad); throw new Exception('Invalid formula accepted'); }
    catch (DefinitionException $e) {
        if ($e->getLanguageKey() !== $case[1] || $e->getDefinitionLine() !== $line) throw $e;
        $count++;
    }
}
$zero = DefinitionParser::parse('zero', str_replace('initial * addition / 100', '0', $body));
if ($zero['outputs'][0]['formula'] !== '0') throw new Exception('Zero constant failed');
$count++;
echo $count . " definition/expression checks passed.\n";
