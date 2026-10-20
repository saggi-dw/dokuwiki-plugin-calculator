<?php
require_once __DIR__.'/../inc/DefinitionParser.php';
use CalculatorPlugin\DefinitionParser;
$count = 0;
foreach (glob(__DIR__.'/../examples/*.txt') as $file) {
    $text = file_get_contents($file);
    $globals = DefinitionParser::globalConstants($text) + ['mehrwertsteuer'=>0.19];
    foreach (DefinitionParser::catalog($text) as $name=>$body) {
        $definition = DefinitionParser::parse($name, $body, $globals);
        if ($definition['name'] !== $name) throw new Exception('Invalid documentation example: '.basename($file));
        $count++;
    }
}
if ($count === 0) throw new Exception('No documentation examples found');
echo "$count documentation examples validated.\n";
