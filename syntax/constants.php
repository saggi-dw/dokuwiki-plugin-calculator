<?php
use dokuwiki\Extension\SyntaxPlugin;
require_once __DIR__ . '/../inc/DefinitionParser.php';
/** Central numeric constants, rendered as a title with validation diagnostics. GPL-2.0. */
class syntax_plugin_calculator_constants extends SyntaxPlugin
{
    public function getType() { return 'protected'; }
    public function getPType() { return 'block'; }
    public function getSort() { return 153; }
    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern('<calcconstants[ \t]*>[\s\S]*?</calcconstants>', $mode, 'plugin_calculator_constants');
    }
    public function handle($match, $state, $pos, Doku_Handler $handler) { return []; }
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        if ($mode !== 'xhtml') return false;
        $renderer->info['cache'] = false;
        $renderer->doc .= '<p class="calculator-definition-title">' . hsc($this->getLang('global_constants')) . '</p>';
        $helper = $this->loadHelper('calculator');
        try { $helper->currentPageConstants(); }
        catch (\CalculatorPlugin\DefinitionException $e) {
            $renderer->doc .= '<div class="error calculator-definition-error" role="alert">' . hsc($helper->definitionError($e)) . '</div>';
        }
        return true;
    }
}
