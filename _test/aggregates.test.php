<?php
require_once __DIR__.'/../inc/DefinitionParser.php';
use CalculatorPlugin\DefinitionParser;
use CalculatorPlugin\ExpressionValidator;
use CalculatorPlugin\DefinitionException;
require_once __DIR__.'/CalculatorTestCase.php';

/** @group plugin_calculator */
class CalculatorAggregatesTest extends CalculatorTestCase
{
    public function testAggregates(): void
    {
        $cases = [];
        foreach (['sum','count','avg','min','max'] as $fn) {
            $cases[] = ["$fn(quantities)", true];
            $cases[] = ["2 + $fn(quantities) * factor", true];
            $cases[] = ["$fn(factor)", false];
            $cases[] = ["$fn(quantities, 2)", false];
            $cases[] = ["$fn(quantities * 2)", false];
        }
        foreach (['sum()', 'sum(unknown)', 'quantities', 'quantities + 1', 'sqrt(quantities)', 'sum(avg(quantities))', 'count(1,2)', 'avg(1,2)', 'sum(quantities).x'] as $formula) $cases[] = [$formula, false];
        foreach (['max(1,2)', 'min(1,2,3)', 'min(avg(quantities), 2)', '-avg(quantities)', 'value / sum(quantities) * 100'] as $formula) $cases[] = [$formula, true];
        foreach ($cases as [$formula, $expected]) {
            $accepted = true;
            try { ExpressionValidator::validate($formula, ['factor','value'], ['quantities']); }
            catch (DefinitionException $e) { $accepted = false; }
            $this->assertFalse($accepted !== $expected, 'Incorrect validation: '.$formula);
        }
        $body = DefinitionParser::catalog(file_get_contents(__DIR__.'/fixtures/dosage_change.txt'))['dosage_change'];
        $definition = DefinitionParser::parse('dosage_change', $body);
        $this->assertFalse($definition['outputs'][2]['type'] !== 'decimal', 'Aggregate must support scalar output');
        // Both kinds of list inputs must make their names available to the validator.
        $decimal = preg_replace('/[ ]{2}pattern = .*\n[ ]{2}flags = iu\n/', '', str_replace(['type = regex-list', 'type = text-list'], ['type = decimal-list', 'type = decimal-list'], $body));
        $decimal = preg_replace('/^[ ]{2}default = "Product[\s\S]*?kg"\n/m', '', $decimal);
        DefinitionParser::parse('dosage_change', $decimal);
        try {
            DefinitionParser::parse('dosage_change', str_replace('sum(scaled_components)', 'sum(unknown)', $body));
            throw new Exception('Unknown list accepted');
        } catch (DefinitionException $e) {
            if ($e->getDefinitionLine() === null) throw $e;
        }
        $this->assertFixture(__DIR__.'/aggregates.json', json_encode(['cases'=>$cases, 'definition'=>$definition], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        $this->addToAssertionCount(1);
    }
}
