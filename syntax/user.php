<?php
use dokuwiki\Extension\SyntaxPlugin;
/** @license GPL 2 @author saggi <saggi@gmx.de> */
class syntax_plugin_calculator_user extends SyntaxPlugin
{
    public function getType() { return 'substition'; }
    public function getPType() { return 'block'; }
    public function getSort() { return 155; }
    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern('<calcuser[ \t]+[a-z][a-z0-9_-]{0,63}[ \t]*>', $mode, 'plugin_calculator_user');
    }
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        preg_match('/^<calcuser\s+([a-z][a-z0-9_-]*)\s*>$/D', $match, $m);
        return ['name' => $m[1]];
    }
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        if ($mode !== 'xhtml') return false;
        global $ID;
        $ui = [];
        foreach (['regex_hint', 'regex_pattern', 'regex_processing', 'regex_invalid_pattern', 'regex_invalid_capture', 'regex_text_requires_value', 'regex_unsupported', 'regex_timeout', 'regex_not_ready', 'calculation_basis', 'constant_name', 'constant_value', 'constant_scope', 'scope_local', 'scope_global', 'locale', 'loading', 'load_error', 'calculate', 'copy', 'copied', 'copy_error', 'number_hint', 'list_hint', 'invalid_number', 'range_error', 'line', 'division_zero', 'circular_reference', 'invalid_expression', 'invalid_template', 'unknown_variable', 'nonfinite', 'too_many_lines', 'empty_list', 'result', 'required', 'copy_select'] as $key) $ui[$key] = $this->getLang($key);
        $renderer->doc .= '<div class="calculator calculator-user" data-calculator="user" data-name="' . hsc($data['name']) . '" data-page="' . hsc($ID) . '" data-ui="' . hsc(json_encode($ui)) . '">';
        $renderer->doc .= '<p class="calculator-loading">' . hsc($ui['loading']) . '</p><noscript>' . hsc($this->getLang('javascript_required')) . '</noscript></div>';
        return true;
    }
}
