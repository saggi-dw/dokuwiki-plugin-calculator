<?php
require_once __DIR__.'/../inc/DefinitionParser.php';
use CalculatorPlugin\DefinitionParser;
require_once __DIR__.'/CalculatorTestCase.php';

/** @group plugin_calculator */
class CalculatorExamplesTest extends CalculatorTestCase
{
    public function testExamples(): void
    {
        $count = 0;
        foreach (glob(__DIR__.'/../examples/*.txt') as $file) {
            $text = file_get_contents($file);
            $globals = DefinitionParser::globalConstants($text) + ['mehrwertsteuer'=>0.19];
            foreach (DefinitionParser::catalog($text) as $name=>$body) {
                $definition = DefinitionParser::parse($name, $body, $globals);
                $this->assertFalse($definition['name'] !== $name, 'Invalid documentation example: '.basename($file));
                $count++;
            }
        }
        $this->assertFalse($count === 0, 'No documentation examples found');
        $this->addToAssertionCount(1);
    }
}
