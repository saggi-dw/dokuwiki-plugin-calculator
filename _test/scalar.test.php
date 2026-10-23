<?php
require_once __DIR__ . '/../inc/DefinitionParser.php';
use CalculatorPlugin\DefinitionParser;
use CalculatorPlugin\DefinitionException;
require_once __DIR__.'/CalculatorTestCase.php';

/** @group plugin_calculator */
class CalculatorScalarTest extends CalculatorTestCase
{
    public function testScalar(): void
    {
        $text = file_get_contents(__DIR__ . '/fixtures/dilution.txt');
        $catalog = DefinitionParser::catalog($text);
        $body = $catalog['dilution'];
        $def = DefinitionParser::parse('dilution', $body);
        $this->assertFalse($def['outputs'][0]['type'] !== 'decimal' || count($def['inputs']) !== 2, 'Scalar definition failed');
        $this->assertFixture(__DIR__ . '/scalar.json', json_encode($def, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        $inferred = DefinitionParser::parse('dilution', str_replace("  type=decimal\n  label = Final concentration", '  label = Final concentration', $body));
        $this->assertFalse($inferred['outputs'][0]['type'] !== 'decimal', 'Inferred scalar type failed');
        foreach ([
            [str_replace('output result:', "output result:\n  source = initial", $body), 'definition_scalar_source'],
            [str_replace("output result:\n  type=decimal", "output result:\n  type=other", $body), 'definition_invalid_output_type'],
            [str_replace("output result:\n  type=decimal", "output result:\n  type=decimal-list", $body), 'definition_invalid_source']
        ] as $test) {
            try { DefinitionParser::parse('test', $test[0]); throw new Exception('Invalid output accepted'); }
            catch (DefinitionException $e) { if ($e->getLanguageKey() !== $test[1]) throw $e; }
        }
        $this->addToAssertionCount(1);
    }
}
