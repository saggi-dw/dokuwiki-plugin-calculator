<?php
use dokuwiki\Extension\SyntaxPlugin;
use CalculatorPlugin\StructFormula;
require_once __DIR__ . '/../inc/StructFormula.php';
/** Interactive and fixed chemical formula calculator. GPL-2.0. */
class syntax_plugin_calculator_chem extends SyntaxPlugin
{
    public function getType() { return 'substition'; }
    public function getPType() { return 'block'; }
    public function getSort() { return 156; }
    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern('<calcchem(?:[ \t]+[^>\r\n]*)?>', $mode, 'plugin_calculator_chem');
    }
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        return ['formula' => trim(substr($match, strlen('<calcchem'), -1))];
    }
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        if ($mode !== 'xhtml') return false;
        $formula = $data['formula'];
        if (StructFormula::isReference($formula)) {
            // Struct values are read at render time, never saved in parser instructions.
            $renderer->info['cache'] = false;
            global $ID, $REV;
            try {
                $formula = StructFormula::resolve($formula, (string)$ID, $REV ?? 0);
            } catch (RuntimeException $e) {
                $renderer->doc .= '<div class="calculator calculator-chem"><p class="calculator-error" role="alert">' . hsc($this->getLang($e->getMessage())) . '</p></div>';
                return true;
            }
        }
        $keys = ['locale', 'copied', 'copy_select', 'invalid_number', 'calculation_basis',
            'chem_title', 'chem_formula', 'chem_part', 'chem_hint', 'chem_molar_mass', 'chem_total', 'chem_rounding',
            'chem_element', 'chem_count', 'chem_weight', 'chem_percent', 'chem_ppm', 'chem_copy_table', 'chem_copy_cell',
            'chem_enter_formula', 'chem_part_error', 'chem_too_long', 'chem_invalid_count', 'chem_too_large',
            'chem_too_deep', 'chem_missing_bracket', 'chem_unknown_element', 'chem_invalid_character',
            'chem_empty_group', 'chem_position', 'chem_basis', 'chem_weights_source', 'chem_fixed'];
        $ui = [];
        foreach ($keys as $key) $ui[$key] = $this->getLang($key);
        $renderer->doc .= '<div class="calculator calculator-chem" data-calculator="chem" data-formula="' . hsc($formula) . '" data-ui="' . hsc(json_encode($ui)) . '">';
        $renderer->doc .= '<noscript>' . hsc($this->getLang('javascript_required')) . '</noscript></div>';
        return true;
    }
}
