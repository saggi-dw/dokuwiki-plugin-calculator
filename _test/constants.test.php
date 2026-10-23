<?php
require_once __DIR__ . '/../inc/DefinitionParser.php';
use CalculatorPlugin\DefinitionParser;
use CalculatorPlugin\DefinitionException;
use CalculatorPlugin\ExpressionValidator;
require_once __DIR__.'/CalculatorTestCase.php';

/** @group plugin_calculator */
class CalculatorConstantsTest extends CalculatorTestCase
{
    public function testConstants(): void
    {
        $count = 0;
        $expectConst = function ($ok) use (&$count) { $this->assertTrue($ok); $count++; };
        $text = file_get_contents(__DIR__ . '/fixtures/constants.txt');
        $globals = DefinitionParser::globalConstants($text);
        $catalog = DefinitionParser::catalog($text);
        $body = $catalog['ppm_with_constants'];
        $definition = DefinitionParser::parse('ppm_with_constants', $body, $globals);
        $expectConst(count($globals) === 2);
        $expectConst(count($definition['constants']) === 2);
        $expectConst($definition['constants'][0]['name'] === 'factor');
        $expectConst($definition['constants'][1]['name'] === 'global.ppm_per_percent');
        $expectConst(!in_array('global.grams_per_kg',array_column($definition['constants'],'name'),true));
        $expectConst($definition['inputs'][1]['type'] === 'const');
        $this->assertFixture(__DIR__ . '/constants.json', json_encode($definition, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        $collision = DefinitionParser::parse('test', str_replace('input factor:', 'input ppm_per_percent:', str_replace(' * factor', ' * ppm_per_percent', $body)), $globals);
        $expectConst(count($collision['constants']) === 2);
        $noLabel = DefinitionParser::parse('test', str_replace("  label = Local factor\n", '', $body), $globals);
        $expectConst($noLabel['inputs'][1]['label'] === 'factor');
        foreach ([
            [str_replace('  value = 1', '', $body), 'constants_value_required'],
            [str_replace('  value = 1', '  value = 1 + 2', $body), 'definition_invalid_number'],
            [str_replace('  value = 1', "  value = 1\n  min = 0", $body), 'definition_unknown_property'],
            [str_replace('global.ppm_per_percent', 'global.missing', $body), 'definition_unknown_variable'],
            [str_replace('global.ppm_per_percent', 'global.ppm_per_percent.extra', $body), 'definition_expression_syntax']
        ] as $case) {
            try { DefinitionParser::parse('test', $case[0], $globals); throw new Exception('Invalid local constant accepted'); }
            catch (DefinitionException $e) { $expectConst($e->getLanguageKey() === $case[1]); }
        }
        foreach ([
            ['a = 1' . "\n" . 'a = 2', 'constants_duplicate_name'],
            ['a = 1+2', 'definition_invalid_number'], ['a.b = 3', 'constants_invalid_line'],
            ['a = global.b', 'definition_invalid_number'], ['a = ', 'definition_invalid_number']
        ] as $case) {
            try { DefinitionParser::parseConstants($case[0]); throw new Exception('Invalid globals accepted'); }
            catch (DefinitionException $e) { $expectConst($e->getLanguageKey() === $case[1]); $expectConst($e->getDefinitionLine() !== null); }
        }
        try { DefinitionParser::globalConstants($text.$text); throw new Exception('Duplicate blocks accepted'); }
        catch (DefinitionException $e) { $expectConst($e->getLanguageKey() === 'constants_multiple_blocks'); }
        $expectConst(ExpressionValidator::validate('global.a + b + global.a', ['global.a','b']) === ['global.a','b']);
        $this->addToAssertionCount(1);
    }
}
