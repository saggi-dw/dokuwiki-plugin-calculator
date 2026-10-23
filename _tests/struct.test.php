<?php
namespace dokuwiki\Extension {
    class SyntaxPlugin {
        public function getLang($key) { return $GLOBALS['language'][$key] ?? $key; }
    }
}
namespace dokuwiki\plugin\struct\meta {
    /** Configurations recorded from the actual upstream Struct parser. */
    class InlineConfigParser {
        private $config;
        public function __construct($inline) {
            $GLOBALS['parserCalls'][] = $inline;
            $fixtures = json_decode(file_get_contents(__DIR__.'/struct-parser.json'), true);
            if (!array_key_exists($inline, $fixtures) || $fixtures[$inline] === null) throw new \RuntimeException('Invalid Struct syntax');
            $this->config = $fixtures[$inline];
        }
        public function getConfig() { return $this->config; }
    }
}
namespace {
    define('AUTH_READ', 1);
    class Doku_Handler {}
    class Doku_Renderer { public $info = ['cache' => true]; public $doc = ''; }
    function hsc($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
    function auth_quickaclcheck($page) { $GLOBALS['aclCalls'][] = $page; return $GLOBALS['acl']; }
    function plugin_load($type, $name) {
        $GLOBALS['loadCalls'][] = [$type, $name];
        return $GLOBALS['structHelper'];
    }
    class FakeStructHelper {
        public $calls = [];
        public $value = 'C32H16CuN8';
        public $fail = false;
        public function getData($page, $schema, $time) {
            $this->calls[] = [$page, $schema, $time];
            if ($this->fail) throw new \RuntimeException('PRIVATE DATABASE DETAIL');
            return [$schema => ['formula' => $this->value, 'chemical formula' => $this->value, 'formula.with.dot' => $this->value]];
        }
    }
    require_once __DIR__.'/../syntax/chem.php';
    $GLOBALS['language'] = [];
    $lang = [];
    require __DIR__.'/../lang/en/lang.php';
    $GLOBALS['language'] = $lang;
    $ID = 'materials:sample'; $REV = 0;
    $GLOBALS['acl'] = AUTH_READ;
    $GLOBALS['aclCalls'] = []; $GLOBALS['loadCalls'] = []; $GLOBALS['parserCalls'] = [];
    $GLOBALS['structHelper'] = new FakeStructHelper();
    $plugin = new syntax_plugin_calculator_chem(); $handler = new Doku_Handler();
    $reference = '{{$material.formula}}';
    $instruction = $plugin->handle('<calcchem '.$reference.'>', 0, 0, $handler);
    $checks = 0;
    function verifyStruct($ok) {
        global $checks;
        if (!$ok) throw new \Exception('Struct integration assertion failed at check '.($checks+1));
        $checks++;
    }
    function renderStruct($data, $mode = 'xhtml') {
        global $plugin;
        $renderer = new Doku_Renderer();
        $result = $plugin->render($mode, $renderer, $data);
        return [$renderer, $result];
    }
    verifyStruct($instruction === ['formula' => $reference]);
    verifyStruct($GLOBALS['loadCalls'] === []);
    [$renderer, $result] = renderStruct($instruction);
    verifyStruct($result === true && $renderer->info['cache'] === false);
    verifyStruct(strpos($renderer->doc, 'data-formula="C32H16CuN8"') !== false);
    verifyStruct($GLOBALS['structHelper']->calls === [['materials:sample', 'material', 0]]);
    verifyStruct($GLOBALS['aclCalls'] === ['materials:sample']);
    $GLOBALS['structHelper']->value = 'TiO2';
    [$renderer] = renderStruct($instruction);
    verifyStruct(strpos($renderer->doc, 'data-formula="TiO2"') !== false);
    $ID = 'materials:other'; $REV = 123456789;
    renderStruct($instruction);
    verifyStruct(end($GLOBALS['structHelper']->calls) === ['materials:other', 'material', 123456789]);

    $before = count($GLOBALS['structHelper']->calls);
    $GLOBALS['acl'] = 0;
    [$renderer] = renderStruct($instruction);
    verifyStruct($renderer->info['cache'] === false && strpos($renderer->doc, $lang['chem_struct_access']) !== false);
    verifyStruct(count($GLOBALS['structHelper']->calls) === $before);
    verifyStruct(strpos($renderer->doc, 'data-calculator=') === false);
    $GLOBALS['acl'] = AUTH_READ;

    $helper = $GLOBALS['structHelper'];
    foreach ([false, new \stdClass()] as $missing) {
        $GLOBALS['structHelper'] = $missing;
        [$renderer] = renderStruct($instruction);
        verifyStruct(strpos($renderer->doc, $lang['chem_struct_missing']) !== false);
    }
    $GLOBALS['structHelper'] = $helper;
    foreach (['', ' ', [], ['TiO2'], null, 42, str_repeat('x', 2001)] as $invalid) {
        $helper->value = $invalid;
        [$renderer] = renderStruct($instruction);
        verifyStruct($renderer->info['cache'] === false && strpos($renderer->doc, $lang['chem_struct_value']) !== false);
    }
    $helper->value = '  NaCl  ';
    [$renderer] = renderStruct($instruction);
    verifyStruct(strpos($renderer->doc, 'data-formula="NaCl"') !== false);
    [$renderer] = renderStruct(['formula' => '{{$material.unknown}}']);
    verifyStruct(strpos($renderer->doc, $lang['chem_struct_value']) !== false);
    $helper->fail = true;
    [$renderer] = renderStruct($instruction);
    verifyStruct(strpos($renderer->doc, $lang['chem_struct_read_error']) !== false);
    verifyStruct(strpos($renderer->doc, 'PRIVATE DATABASE DETAIL') === false);
    $helper->fail = false;

    foreach (['H2O{{$material.formula}}', '{{$material.formula}} + H2O', '{{$material.formula'] as $invalid) {
        [$renderer] = renderStruct(['formula' => $invalid]);
        verifyStruct(strpos($renderer->doc, hsc($lang['chem_struct_reference'])) !== false);
    }
    $helper->value = 'TiO2';
    foreach (['{{$ material.formula }}', '{{$ "material"."formula" }}', '{{$material."chemical formula"}}', '{{$material."formula.with.dot"}}'] as $referenceWithFormatting) {
        [$renderer] = renderStruct(['formula' => $referenceWithFormatting]);
        verifyStruct(strpos($renderer->doc, 'data-formula="TiO2"') !== false);
        verifyStruct($renderer->info['cache'] === false);
        verifyStruct(end($GLOBALS['parserCalls']) === substr($referenceWithFormatting, 3, -2));
    }
    foreach (['{{$material.formula.other}}', '{{$materials:other.material.formula}}', '{{$material.formula ? filter: "formula = H2O"}}', '{{$material.formula,unknown}}', '{{$material alias.formula}}'] as $unsupported) {
        $calls = count($helper->calls);
        [$renderer] = renderStruct(['formula' => $unsupported]);
        verifyStruct(strpos($renderer->doc, hsc($lang['chem_struct_unsupported'])) !== false);
        verifyStruct(count($helper->calls) === $calls);
    }
    [$renderer] = renderStruct(['formula' => '{{$material.formula ? filter: invalid}}']);
    verifyStruct(strpos($renderer->doc, hsc($lang['chem_struct_reference'])) !== false);
    $helper->value = '\"><script>alert(1)</script>';
    [$renderer] = renderStruct($instruction);
    verifyStruct(strpos($renderer->doc, '<script>') === false && strpos($renderer->doc, '&lt;script&gt;') !== false);
    $before = count($GLOBALS['loadCalls']);
    $parserBefore = count($GLOBALS['parserCalls']);
    foreach (['TiO2', ''] as $literal) {
        [$renderer] = renderStruct(['formula' => $literal]);
        verifyStruct($renderer->info['cache'] === true);
        verifyStruct(strpos($renderer->doc, 'data-formula="'.$literal.'"') !== false);
    }
    [$renderer, $result] = renderStruct($instruction, 'metadata');
    verifyStruct($result === false && $renderer->doc === '' && $renderer->info['cache'] === true);
    verifyStruct(count($GLOBALS['loadCalls']) === $before);
    verifyStruct(count($GLOBALS['parserCalls']) === $parserBefore);
    foreach (['de', 'en'] as $locale) {
        $lang = [];
        require __DIR__.'/../lang/'.$locale.'/lang.php';
        foreach (['chem_struct_reference','chem_struct_access','chem_struct_missing','chem_struct_read_error','chem_struct_value','chem_struct_unsupported'] as $key) {
            verifyStruct(!empty($lang[$key]) && $lang[$key] !== $key);
        }
    }
    echo "$checks Struct integration checks passed.\n";
}
