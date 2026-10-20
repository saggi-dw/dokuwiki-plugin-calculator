<?php
use dokuwiki\Extension\SyntaxPlugin;
require_once __DIR__ . '/../inc/DefinitionParser.php';
/** Definition blocks show a title and, when invalid, a localized diagnostic. GPL-2.0. */
class syntax_plugin_calculator_definition extends SyntaxPlugin
{
    public function getType() { return 'protected'; }
    public function getPType() { return 'block'; }
    public function getSort() { return 154; }
    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern('<calcdefinition(?=[ \t>])[^>]*>[\s\S]*?</calcdefinition>', $mode, 'plugin_calculator_definition');
    }
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        preg_match('/^<calcdefinition[ \t]*([^>]*)>/', $match, $opening);
        $body = substr($match, strlen($opening[0]), -strlen('</calcdefinition>'));
        $name = trim($opening[1]);
        preg_match('/^title\s*=\s*(.+)$/m', $body, $title);
        return ['name' => $name, 'body' => $body, 'title' => isset($title[1]) ? trim($title[1]) : ($name ?: 'calcdefinition')];
    }
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        if ($mode !== 'xhtml') return false;
        // Check on render, not in cached parser instructions. This also handles previews.
        $renderer->info['cache'] = false;
        $renderer->doc .= '<p class="calculator-definition-title">' . hsc($data['title']) . '</p>';
        try {
            $names = $renderer->info['calculator_definition_names'] ?? [];
            if (isset($names[$data['name']])) throw new \CalculatorPlugin\DefinitionException('definition_duplicate_name', [$data['name']]);
            $names[$data['name']] = true;
            $renderer->info['calculator_definition_names'] = $names;
            $helper = $this->loadHelper('calculator');
            \CalculatorPlugin\DefinitionParser::parse($data['name'], $data['body'], $helper->currentPageConstants());
        } catch (\CalculatorPlugin\DefinitionException $e) {
            $helper = $this->loadHelper('calculator');
            $renderer->doc .= '<div class="error calculator-definition-error" role="alert">' . hsc($helper->definitionError($e)) . '</div>';
        }
        return true;
    }
}
