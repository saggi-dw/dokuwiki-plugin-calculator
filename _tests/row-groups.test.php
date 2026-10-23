<?php
require_once __DIR__.'/../inc/DefinitionParser.php';
use CalculatorPlugin\DefinitionParser;
use CalculatorPlugin\DefinitionException;
use CalculatorPlugin\ExpressionValidator;
$body = DefinitionParser::catalog(file_get_contents(__DIR__.'/../examples/multi_value_text.txt'))['line_prices'];
$def = DefinitionParser::parse('line_prices', $body);
$checks = 0;
function checkGroups($ok) { global $checks; if (!$ok) throw new Exception('Row group assertion failed'); $checks++; }
checkGroups($def['inputs'][0]['groups'] === ['quantity','count','price']);
checkGroups($def['outputs'][0]['type'] === 'decimal-list');
$inferred = DefinitionParser::parse('line_prices', str_replace("  type = decimal-list\n", '', $body));
checkGroups($inferred['outputs'][0]['type'] === 'decimal-list');
foreach ([
    [str_replace('row.price','row.unknown',$body),'definition_unknown_variable'],
    [str_replace('row.price','price',$body),'definition_unknown_variable'],
    [str_replace('row.price','value',$body),'definition_unknown_variable'],
    [str_replace('sum(line_totals)','sum(items)',$body),'definition_unknown_function'],
    [str_replace('type = decimal-list','type = text-list',$body),'regex_text_requires_value'],
    [str_replace('(?<price>','(?<Price>',$body),'regex_invalid_group_name'],
    [str_replace('(?<price>','(?<'.str_repeat('p',65).'>',$body),['regex_invalid_group_name','regex_invalid_pattern']],
    [str_replace('sum(line_totals)','row.quantity',$body),'definition_unknown_variable'],
] as [$invalid,$key]) {
    try { DefinitionParser::parse('line_prices',$invalid); throw new Exception('Invalid row definition accepted'); }
    catch (DefinitionException $e) { if (!in_array($e->getLanguageKey(), (array)$key, true)) throw new Exception('Unexpected error: '.$e->getLanguageKey()); checkGroups($e->getDefinitionLine() !== null); }
}
$legacy = "title = Legacy\ninput rows:\n  type = regex-list\n  label = Rows\n  pattern = (?<prefix>[A-Z]+) (?<value>\\d+) x (?<count>\\d+)\noutput result:\n  source = rows\n  label = Result\n  formula = value * row.count";
$legacyDef=DefinitionParser::parse('legacy',$legacy);
checkGroups($legacyDef['outputs'][0]['type'] === 'text-list');
checkGroups($legacyDef['inputs'][0]['groups'] === ['prefix','value','count']);
$numericLegacy=DefinitionParser::parse('legacy',str_replace("output result:\n", "output result:\n  type = decimal-list\n",$legacy));
checkGroups($numericLegacy['outputs'][0]['type'] === 'decimal-list');
$upperLegacy=DefinitionParser::parse('legacy',str_replace('(?<prefix>', '(?<PREFIX>', $legacy));
checkGroups($upperLegacy['inputs'][0]['groups'] === ['value','count']);
$variables=['row.quantity','row.count','row.price','global.factor','price'];
$cases=[];
foreach (['row.quantity * row.count * row.price','row.price + price','row.price * global.factor','max(row.quantity,2)','-row.price','row.price^2'] as $formula) $cases[]=[$formula,true];
foreach (['row.unknown','row.quantity.extra','row.quantity()','row[quantity]','row.__proto__','row.constructor','window.row.price'] as $formula) $cases[]=[$formula,false];
foreach($cases as [$formula,$expected]) {
    $accepted=true;
    try { ExpressionValidator::validate($formula,$variables); } catch(DefinitionException $e) { $accepted=false; }
    checkGroups($accepted===$expected);
}
file_put_contents(__DIR__.'/row-groups.json',json_encode(['definition'=>$def,'legacy'=>$legacyDef,'variables'=>$variables,'cases'=>$cases],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo "$checks row group parser and expression checks passed.\n";
