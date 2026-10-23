<?php
namespace dokuwiki\Extension {
    class SyntaxPlugin {
        public function getLang($key) { return $GLOBALS['messages'][$key] ?? $key; }
    }
}
namespace {
    define('AUTH_READ', 1);
    class Doku_Renderer { public $info = ['cache' => true]; public $doc = ''; }
    function hsc($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
    function auth_quickaclcheck($page) { return AUTH_READ; }
    function plugin_load($type, $name) { $GLOBALS['loads'][] = [$type, $name]; return $GLOBALS['helper']; }
    $loads = []; $autoloads = []; $helper = false;
    spl_autoload_register(function ($name) { $GLOBALS['autoloads'][] = $name; });
    $lang = []; require __DIR__.'/../lang/en/lang.php'; $messages = $lang;
    require __DIR__.'/../syntax/chem.php';
    $plugin = new syntax_plugin_calculator_chem();
    $ID = 'materials:sample'; $REV = 0;
    $checks = 0;
    foreach (['', 'TiO2'] as $formula) {
        $renderer = new Doku_Renderer();
        if (!$plugin->render('xhtml', $renderer, ['formula' => $formula]) || !$renderer->info['cache']) throw new \Exception('Literal chemistry failed without Struct');
        $checks++;
    }
    if ($loads || $autoloads) throw new \Exception('Literal chemistry tried to load Struct');
    $checks++;
    $renderer = new Doku_Renderer();
    $plugin->render('xhtml', $renderer, ['formula' => '{{$material.formula}}']);
    if (strpos($renderer->doc, $messages['chem_struct_missing']) === false || $renderer->info['cache']) throw new \Exception('Missing Struct helper was not handled');
    $checks++;
    $helper = new class { public function getData($page, $schema, $revision) { throw new \Exception('Unavailable parser must be detected before reading'); } };
    $renderer = new Doku_Renderer();
    $plugin->render('xhtml', $renderer, ['formula' => '{{$material.formula}}']);
    if (strpos($renderer->doc, $messages['chem_struct_missing']) === false || $renderer->info['cache']) throw new \Exception('Missing Struct parser was not handled');
    $checks++;
    if ($autoloads !== ['dokuwiki\\plugin\\struct\\meta\\InlineConfigParser']) throw new \Exception('Unexpected dependency autoload');
    $checks++;
    echo "$checks optional Struct dependency checks passed.\n";
}
