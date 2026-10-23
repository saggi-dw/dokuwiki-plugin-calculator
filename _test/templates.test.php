<?php
require_once __DIR__.'/../inc/DefinitionParser.php';
use CalculatorPlugin\DefinitionParser;
use CalculatorPlugin\DefinitionException;
require_once __DIR__.'/CalculatorTestCase.php';

/** @group plugin_calculator */
class CalculatorTemplatesTest extends CalculatorTestCase
{
    public function testTemplates(): void
    {
        $text = file_get_contents(__DIR__.'/../examples/templated_output.txt');
        $catalog = DefinitionParser::catalog($text);
        $body = $catalog['formatted_line_prices'];
        $definition = DefinitionParser::parse('formatted_line_prices', $body);
        $this->assertFalse($definition['outputs'][0]['template'] !== '{line} → {label}: {value} {unit}', 'Template was modified');
        $scalar = "title = Template test\noutput result:\n  label = Result\n  formula = 1\n";
        $valid = DefinitionParser::parse('test', $scalar."  template = {label}: {value} {unit}\n");
        $this->assertFalse($valid['outputs'][0]['template'] !== '{label}: {value} {unit}', 'Scalar template failed');
        $count = 2;
        foreach (['{unknown}', '{value', 'value}', '', str_repeat('x', 2001), '{line}'] as $template) {
            try {
                DefinitionParser::parse('test', $scalar."  template = $template\n");
                throw new Exception('Invalid template accepted');
            } catch (DefinitionException $e) {
                $expected = $template === '{line}' ? 'definition_template_requires_list' : 'definition_invalid_template';
                if ($e->getLanguageKey() !== $expected || $e->getDefinitionLine() !== 5) throw $e;
                $count++;
            }
        }
        $plain = DefinitionParser::parse('test', $scalar);
        $this->assertFalse(isset($plain['outputs'][0]['template']), 'Default template changed');
        $count++;
        $this->assertFixture(__DIR__.'/templates.json', json_encode($definition, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n");
        $this->addToAssertionCount(1);
    }
}
