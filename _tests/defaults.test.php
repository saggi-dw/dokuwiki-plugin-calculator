<?php
require_once __DIR__.'/../inc/DefinitionParser.php';
use CalculatorPlugin\DefinitionParser;
use CalculatorPlugin\DefinitionException;
$count = 0;
function verifyDefault($condition) { global $count; if (!$condition) throw new Exception('Default assertion failed'); $count++; }
$body = <<<'DEF'
title = Default list
input quantities:
  type = regex-list
  label = Components
  default="Product	Quantity
ITEM-A	0,500 kg
ITEM-B	0,060 kg
ITEM-C	0,040 kg"
  pattern = (?<value>[+-]?\d+(?:[.,]\d+)?)(?=\s+kg(?:\s|$))
  flags = iu
  min = 0
  break = true
output total:
  label = Total
  formula = sum(quantities)
DEF;
$definition = DefinitionParser::parse('defaults', $body);
$expected = "Product\tQuantity\nITEM-A\t0,500 kg\nITEM-B\t0,060 kg\nITEM-C\t0,040 kg";
verifyDefault($definition['inputs'][0]['default'] === $expected);
verifyDefault($definition['inputs'][0]['break'] === true);
verifyDefault(DefinitionParser::parse('defaults', str_replace("\n", "\r\n", $body))['inputs'][0]['default'] === $expected);
$template = "title = Test\ninput quantities:\n  type = regex-list\n  label = Values\n%s\n  pattern = (?<value>\\d+)\noutput total:\n  label = Total\n  formula = sum(quantities)";
foreach (["\f", "\v", " \t\f\v"] as $space) {
    $property = '  default'.$space.'='.$space.'"x"';
    verifyDefault(DefinitionParser::parse('defaults', sprintf($template, $property))['inputs'][0]['default'] === 'x');
}
verifyDefault(DefinitionParser::parse('defaults', sprintf($template, "  default = \"\fx\""))['inputs'][0]['default'] === "\fx");
verifyDefault(DefinitionParser::parse('defaults', sprintf($template, '  default =\f"x"'))['inputs'][0]['default'] === '\f"x"');
$literal = <<<'TEXT'
  default = "  Text \"A\" C:\\Temp\file.txt

# not a comment
input not_a_field:
  label = part of the text – café  "
TEXT;
$parsed = DefinitionParser::parse('defaults', sprintf($template, $literal));
verifyDefault($parsed['inputs'][0]['default'] === "  Text \"A\" C:\\Temp\\file.txt\n\n# not a comment\ninput not_a_field:\n  label = part of the text – café  ");
foreach (['  default = ""'=>'', '  default = "0.150 kg"'=>'0.150 kg', '  default = 0,150 kg'=>'0,150 kg'] as $property=>$value) {
    verifyDefault(DefinitionParser::parse('defaults', sprintf($template, $property))['inputs'][0]['default'] === $value);
}
foreach (['  default = "open', '  default = "closed" trailing'] as $bad) {
    try { DefinitionParser::parse('defaults', sprintf($template, $bad)); throw new Exception('Invalid quote accepted'); }
    catch (DefinitionException $e) { verifyDefault($e->getLanguageKey() === 'definition_invalid_default_text' && $e->getDefinitionLine() === 5); }
}
try { DefinitionParser::parse('defaults', sprintf($template, "  default = \"1 kg\"\n  default = \"2 kg\"")); throw new Exception('Duplicate accepted'); }
catch (DefinitionException $e) { verifyDefault($e->getLanguageKey() === 'definition_duplicate_property' && $e->getDefinitionLine() === 6); }
foreach ([str_repeat('x',200001), str_repeat("\n",10000)] as $large) {
    try { DefinitionParser::parse('defaults', sprintf($template, '  default = "'.$large.'"')); throw new Exception('Oversize default accepted'); }
    catch (DefinitionException $e) { verifyDefault($e->getLanguageKey() === 'definition_default_too_large' && $e->getDefinitionLine() === 5); }
}
$decimal = "title = Numbers\ninput quantities:\n  type = decimal-list\n  label = Numbers\n  min = 0\n  max = 10\n  default = \"1.5\n\n 2,5 \n0\"\noutput total:\n  label = Total\n  formula = sum(quantities)";
$decimalDefinition=DefinitionParser::parse('defaults',$decimal);
verifyDefault($decimalDefinition['inputs'][0]['default'] === "1.5\n\n 2,5 \n0");
foreach (['-1'=>'definition_default_out_of_range','11'=>'definition_default_out_of_range','abc'=>'definition_invalid_number'] as $bad=>$key) {
    try { DefinitionParser::parse('defaults', str_replace('1.5', (string)$bad, $decimal)); throw new Exception('Invalid numeric default accepted'); }
    catch (DefinitionException $e) { verifyDefault($e->getLanguageKey() === $key && $e->getDefinitionLine() === 7); }
}
$scalar="title = Scalar\ninput number:\n  type = decimal\n  label = Number\n  default = \"2.5\"\noutput result:\n  label = Result\n  formula = number";
verifyDefault(DefinitionParser::parse('defaults',$scalar)['inputs'][0]['default'] === 2.5);
file_put_contents(__DIR__.'/defaults.json',json_encode(['definition'=>$definition,'decimal'=>$decimalDefinition,'scalar'=>DefinitionParser::parse('defaults',$scalar)],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo "$count default-value checks passed.\n";
