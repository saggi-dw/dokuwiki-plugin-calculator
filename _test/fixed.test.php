<?php
require_once __DIR__.'/../inc/DefinitionParser.php';
use CalculatorPlugin\DefinitionParser;
use CalculatorPlugin\DefinitionException;
require_once __DIR__.'/CalculatorTestCase.php';

/** @group plugin_calculator */
class CalculatorFixedTest extends CalculatorTestCase
{
    public function testFixed(): void
    {
        $body="title = Test\noutput result:\n  label = Result\n  formula = 0.15\n  decimals = 3\n";
        foreach ([null => false, 'true' => true, 'false' => false] as $setting => $expected) {
            $definition = DefinitionParser::parse('test', $body . ($setting === '' ? '' : "  fixed = $setting\n"));
            $this->assertFalse($definition['outputs'][0]['fixed'] !== $expected, 'Incorrect fixed setting');
        }
        try {
            DefinitionParser::parse('test', $body . "  fixed = yes\n");
            throw new Exception('Invalid fixed setting accepted');
        } catch (DefinitionException $e) {
            if ($e->getLanguageKey() !== 'definition_invalid_boolean' || $e->getParameters() !== ['fixed'] || $e->getDefinitionLine() !== 6) throw $e;
        }
        $this->addToAssertionCount(1);
    }
}
