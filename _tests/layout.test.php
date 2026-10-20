<?php
require_once __DIR__.'/../inc/DefinitionParser.php';
use CalculatorPlugin\DefinitionParser;
use CalculatorPlugin\DefinitionException;
$text=file_get_contents(__DIR__.'/fixtures/definitions.txt');
$body=DefinitionParser::catalog($text)['recipe'];
$definition=DefinitionParser::parse('recipe',$body);
if($definition['inputs'][2]['break']!==true)throw new Exception('Input break not parsed');
$definition=DefinitionParser::parse('recipe',str_replace('break = true','break = false',$body));
if($definition['inputs'][2]['break']!==false)throw new Exception('False not parsed');
$definition=DefinitionParser::parse('recipe',str_replace('output result:',"output result:\n  break = true",$body));
if($definition['outputs'][0]['break']!==true)throw new Exception('Output break not parsed');
try{DefinitionParser::parse('recipe',str_replace('break = true','break = yes',$body));throw new Exception('Bad boolean accepted');}
catch(DefinitionException $e){if($e->getLanguageKey()!=='definition_invalid_boolean'||$e->getDefinitionLine()===null)throw $e;}
echo "4 layout checks passed.\n";
