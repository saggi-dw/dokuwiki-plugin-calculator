<?php
require_once __DIR__.'/../inc/DefinitionParser.php';
use CalculatorPlugin\DefinitionParser;
use CalculatorPlugin\DefinitionException;
$body="title = Test\noutput result:\n  label = Result\n  formula = 0.15\n  decimals = 3\n";
foreach ([null => false, 'true' => true, 'false' => false] as $setting => $expected) {
    $definition = DefinitionParser::parse('test', $body . ($setting === '' ? '' : "  fixed = $setting\n"));
    if ($definition['outputs'][0]['fixed'] !== $expected) throw new Exception('Incorrect fixed setting');
}
try {
    DefinitionParser::parse('test', $body . "  fixed = yes\n");
    throw new Exception('Invalid fixed setting accepted');
} catch (DefinitionException $e) {
    if ($e->getLanguageKey() !== 'definition_invalid_boolean' || $e->getParameters() !== ['fixed'] || $e->getDefinitionLine() !== 6) throw $e;
}
echo "4 fixed-format parser checks passed.\n";
