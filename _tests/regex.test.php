<?php
require_once __DIR__ . '/../inc/DefinitionParser.php';
use CalculatorPlugin\DefinitionParser;
use CalculatorPlugin\DefinitionException;
$text=file_get_contents(__DIR__ . '/fixtures/recipe_text.txt');
$body=DefinitionParser::catalog($text)['recipe_text'];
$def=DefinitionParser::parse('recipe_text',$body);
if($def['inputs'][2]['type']!=='regex-list' || $def['outputs'][0]['type']!=='text-list')throw new Exception('Wrong regex types');
file_put_contents(__DIR__.'/regex.json',json_encode($def, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
$inferred=DefinitionParser::parse('recipe_text',str_replace("  type = text-list\n",'',$body));
if($inferred['outputs'][0]['type']!=='text-list')throw new Exception('Wrong inferred output');
$count=2;
foreach([
 [preg_replace('/^  pattern = .*$/m','  pattern = [(?<value>)]',$body),'regex_named_group_required'],
 [str_replace('(?<value>','(',$body),'regex_named_group_required'],
 [str_replace('  flags = u','  flags = g',$body),'regex_invalid_flags'],
 [str_replace('  flags = u','  flags = uu',$body),'regex_invalid_flags'],
 [preg_replace('/^  pattern = .*$/m','  pattern = (',$body),'regex_invalid_pattern'],
 [str_replace('  type = text-list','  type = decimal',$body),'definition_scalar_source'],
 [str_replace('  type = regex-list','  type = decimal-list',$body),'definition_unknown_property']
] as $case){
 try{DefinitionParser::parse('test',$case[0]);throw new Exception('Bad regex definition accepted');}
 catch(DefinitionException $e){if($e->getLanguageKey()!==$case[1])throw $e;$count++;}
}
echo $count." regex definition checks passed.\n";
