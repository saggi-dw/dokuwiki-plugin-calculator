<?php
require_once __DIR__.'/../inc/DefinitionParser.php';
use CalculatorPlugin\DefinitionParser;
use CalculatorPlugin\DefinitionException;
$body=DefinitionParser::catalog(file_get_contents(__DIR__.'/fixtures/definitions.txt'))['recipe'];
$implicit=DefinitionParser::parse('recipe',$body);
if($implicit['showwork']!==false)throw new Exception('Default must be false');
$visible=DefinitionParser::parse('recipe',"showwork = true\n".$body);
if($visible['showwork']!==true)throw new Exception('Explicit true must be honored');
$hidden=DefinitionParser::parse('recipe',"showwork = false\n".$body);
if($hidden['showwork']!==false)throw new Exception('Explicit false must be honored');
foreach([['showwork = yes','definition_invalid_boolean'],["showwork = false\nshowwork = true",'definition_duplicate_property']] as $case){
 try{DefinitionParser::parse('recipe',$case[0]."\n".$body);throw new Exception('Invalid setting accepted');}
 catch(DefinitionException $e){if($e->getLanguageKey()!==$case[1]||$e->getDefinitionLine()===null)throw $e;}
}
if($implicit['outputs']!==$visible['outputs']||$implicit['constants']!==$visible['constants'])throw new Exception('Display setting changed calculations');
echo "6 showwork checks passed.\n";
