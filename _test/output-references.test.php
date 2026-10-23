<?php
require_once __DIR__.'/../inc/DefinitionParser.php';
use CalculatorPlugin\DefinitionParser;
use CalculatorPlugin\DefinitionException;
require_once __DIR__.'/CalculatorTestCase.php';

/** @group plugin_calculator */
class CalculatorOutputReferencesTest extends CalculatorTestCase
{
    public function testOutputReferences(): void
    {
        $body = <<<'DEF'
        title = Chained outputs
        input quantities:
          type = decimal-list
          label = Quantities
        output gross_total:
          label = Gross total
          formula = net_total + tax
        output tax:
          label = Tax
          formula = sum(net_amounts) * global.vat
        output net_total:
          label = Net total
          formula = sum(net_amounts)
        output net_amounts:
          type = decimal-list
          source = quantities
          label = Net amounts
          formula = value / 3
          decimals = 2
          fixed = true
        DEF;
        $definition = DefinitionParser::parse('chained', $body, ['vat'=>0.19]);
        $this->assertFalse(array_column($definition['outputs'], 'name') !== ['gross_total','tax','net_total','net_amounts'], 'Display order changed');
        $this->assertFalse(count($definition['constants']) !== 1, 'Global constant reference lost');
        $cases = [
            ['net_total + tax', 'gross_total', 'definition_circular_reference'],
            ['sum(net_amounts) * global.vat', 'gross_total', 'definition_circular_reference'],
            ['value / 3', 'value + net_total', 'definition_circular_reference'],
            ['net_total + tax', 'net_amounts + 1', 'definition_unknown_variable'],
            ['sum(net_amounts)', 'sum(net_total)', 'definition_unknown_function'],
            ['sum(net_amounts)', 'sum(unknown)', 'definition_unknown_function'],
        ];
        foreach ($cases as [$from, $to, $key]) {
            try { DefinitionParser::parse('chained', str_replace($from, $to, $body), ['vat'=>0.19]); throw new Exception('Invalid reference accepted: '.$to); }
            catch (DefinitionException $e) {
                if ($e->getLanguageKey() !== $key || $e->getDefinitionLine() === null) throw $e;
                $this->assertFalse($key === 'definition_circular_reference' && strpos($e->getParameters()[0], ' -> ') === false, 'Missing cycle path');
            }
        }
        $regex = str_replace('type = decimal-list', 'type = regex-list', $body);
        // Use an actual input pattern and a text-list output to check inferred types too.
        $regex = str_replace("  type = regex-list\n  label = Quantities", "  type = regex-list\n  pattern = (?<value>\\d+)\n  label = Quantities", $regex);
        $regex = str_replace("  type = regex-list\n  source = quantities", "  source = quantities", $regex);
        $textDefinition = DefinitionParser::parse('chained', $regex, ['vat'=>0.19]);
        $this->assertFalse($textDefinition['outputs'][3]['type'] !== 'text-list', 'Inferred text-list output not registered');
        $exampleBody = DefinitionParser::catalog(file_get_contents(__DIR__.'/fixtures/vat.txt'))['vat'];
        $example = DefinitionParser::parse('vat', $exampleBody, ['vat'=>0.19]);
        $this->assertFalse($example['outputs'][3]['formula'] !== 'net_total + tax', 'Invalid VAT example');
        $this->assertFixture(__DIR__.'/output-references.json', json_encode(['definition'=>$definition,'textDefinition'=>$textDefinition,'example'=>$example], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        $this->addToAssertionCount(1);
    }
}
