<?php
require_once __DIR__ . '/../inc/DefinitionParser.php';
use CalculatorPlugin\DefinitionParser;
use CalculatorPlugin\ExpressionValidator;
use CalculatorPlugin\DefinitionException;
require_once __DIR__.'/CalculatorTestCase.php';

/** @group plugin_calculator */
class CalculatorValidationTest extends CalculatorTestCase
{
    public function testValidation(): void
    {
        $cases = [];
        foreach (['0', 'value * factor / 100', '-2^2', '2^-2', '2^3^2', 'min(value, 2, 3)', 'max(abs(-value),sqrt(16))', 'pow(2,3)%2', 'round(2.5)+floor(1.1)+ceil(1.1)', '(value+factor)*.5', '1/0'] as $formula) $cases[] = [$formula, ['value','factor'], true];
        foreach (['', 'value *', '(value+1', 'pow(2)', 'sqrt()', 'min(2)', 'unknown+1', 'alert(1)', 'value[0]', 'value.constructor', '1 2', '1;2', '1,5', 'foo(1)', 'value factor', str_repeat('(',40).'1'.str_repeat(')',40)] as $formula) $cases[] = [$formula, ['value','factor'], false];
        $count = 0;
        foreach ($cases as $case) {
            $accepted = true;
            try { ExpressionValidator::validate($case[0], $case[1]); }
            catch (DefinitionException $e) {
                $accepted = false;
                foreach (['de','en'] as $language) {
                    $lang = []; require __DIR__ . '/../lang/' . $language . '/lang.php';
                    $this->assertFalse(!isset($lang[$e->getLanguageKey()]), 'Missing translation');
                    $this->assertNotSame('', vsprintf($lang[$e->getLanguageKey()], $e->getParameters()));
                }
            }
            $this->assertFalse($accepted !== $case[2], 'Wrong acceptance: ' . $case[0]);
            $count++;
        }
        $this->assertFixture(__DIR__ . '/expressions.json', json_encode($cases, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        $source = file_get_contents(__DIR__ . '/fixtures/dilution.txt');
        $body = DefinitionParser::catalog($source)['dilution'];
        foreach ([['initial * (', 'definition_expression_syntax'], ['unknown_input * addition', 'definition_unknown_variable'], ['pow(initial)', 'definition_function_arguments'], ['value', 'definition_unknown_variable']] as $case) {
            $bad = str_replace('initial * addition / 100', $case[0], $body);
            $line = array_search('  formula = ' . $case[0], explode("\n", $bad), true) + 1;
            try { DefinitionParser::parse('dilution', $bad); throw new Exception('Invalid formula accepted'); }
            catch (DefinitionException $e) {
                if ($e->getLanguageKey() !== $case[1] || $e->getDefinitionLine() !== $line) throw $e;
                $count++;
            }
        }
        $zero = DefinitionParser::parse('zero', str_replace('initial * addition / 100', '0', $body));
        $this->assertFalse($zero['outputs'][0]['formula'] !== '0', 'Zero constant failed');
        $count++;
        $this->addToAssertionCount(1);
    }
}
