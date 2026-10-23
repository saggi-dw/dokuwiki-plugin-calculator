<?php
require_once __DIR__.'/../inc/DefinitionParser.php';
use CalculatorPlugin\DefinitionParser;
use CalculatorPlugin\DefinitionException;
require_once __DIR__.'/CalculatorTestCase.php';

/** @group plugin_calculator */
class CalculatorShowworkTest extends CalculatorTestCase
{
    public function testShowwork(): void
    {
        $body=DefinitionParser::catalog(file_get_contents(__DIR__.'/fixtures/definitions.txt'))['recipe'];
        $implicit=DefinitionParser::parse('recipe',$body);
        $this->assertFalse($implicit['showwork']!==false, 'Default must be false');
        $visible=DefinitionParser::parse('recipe',"showwork = true\n".$body);
        $this->assertFalse($visible['showwork']!==true, 'Explicit true must be honored');
        $hidden=DefinitionParser::parse('recipe',"showwork = false\n".$body);
        $this->assertFalse($hidden['showwork']!==false, 'Explicit false must be honored');
        foreach([['showwork = yes','definition_invalid_boolean'],["showwork = false\nshowwork = true",'definition_duplicate_property']] as $case){
         try{DefinitionParser::parse('recipe',$case[0]."\n".$body);throw new Exception('Invalid setting accepted');}
         catch(DefinitionException $e){if($e->getLanguageKey()!==$case[1]||$e->getDefinitionLine()===null)throw $e;}
        }
        $this->assertFalse($implicit['outputs']!==$visible['outputs']||$implicit['constants']!==$visible['constants'], 'Display setting changed calculations');
        $this->addToAssertionCount(1);
    }
}
