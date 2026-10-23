<?php
require_once __DIR__.'/../inc/DefinitionParser.php';
use CalculatorPlugin\DefinitionParser;
use CalculatorPlugin\DefinitionException;
$text = file_get_contents(__DIR__.'/../examples/templated_output.txt');
$catalog = DefinitionParser::catalog($text);
$body = $catalog['formatted_line_prices'];
$definition = DefinitionParser::parse('formatted_line_prices', $body);
if ($definition['outputs'][0]['template'] !== '{line} → {label}: {value} {unit}') throw new Exception('Template was modified');
$scalar = "title = Template test\noutput result:\n  label = Result\n  formula = 1\n";
$valid = DefinitionParser::parse('test', $scalar."  template = {label}: {value} {unit}\n");
if ($valid['outputs'][0]['template'] !== '{label}: {value} {unit}') throw new Exception('Scalar template failed');
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
if (isset($plain['outputs'][0]['template'])) throw new Exception('Default template changed');
$count++;
file_put_contents(__DIR__.'/templates.json', json_encode($definition, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n");
echo "$count output-template parser checks passed.\n";
