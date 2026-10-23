<?php
require_once __DIR__.'/../inc/DefinitionParser.php';
use CalculatorPlugin\DefinitionParser;
use CalculatorPlugin\DefinitionException;
require_once __DIR__.'/CalculatorTestCase.php';

/** @group plugin_calculator */
class CalculatorLayoutTest extends CalculatorTestCase
{
    public function testLayout(): void
    {
        $text=file_get_contents(__DIR__.'/fixtures/definitions.txt');
        $body=DefinitionParser::catalog($text)['recipe'];
        $definition=DefinitionParser::parse('recipe',$body);
        $this->assertFalse($definition['inputs'][2]['break']!==true, 'Input break not parsed');
        $definition=DefinitionParser::parse('recipe',str_replace('break = true','break = false',$body));
        $this->assertFalse($definition['inputs'][2]['break']!==false, 'False not parsed');
        $definition=DefinitionParser::parse('recipe',str_replace('output result:',"output result:\n  break = true",$body));
        $this->assertFalse($definition['outputs'][0]['break']!==true, 'Output break not parsed');
        try{DefinitionParser::parse('recipe',str_replace('break = true','break = yes',$body));throw new Exception('Bad boolean accepted');}
        catch(DefinitionException $e){if($e->getLanguageKey()!=='definition_invalid_boolean'||$e->getDefinitionLine()===null)throw $e;}
        $this->addToAssertionCount(1);
    }
}
