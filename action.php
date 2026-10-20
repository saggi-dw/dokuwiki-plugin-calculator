<?php
use dokuwiki\Extension\ActionPlugin;
use dokuwiki\Extension\EventHandler;
use dokuwiki\Extension\Event;
/** @license GPL 2 @author saggi <saggi@gmx.de> */
class action_plugin_calculator extends ActionPlugin
{
    public function register(EventHandler $controller)
    {
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'definitionRequest');
        $controller->register_hook('JS_SCRIPT_LIST', 'BEFORE', $this, 'registerScripts');
        $controller->register_hook('PARSER_CACHE_USE', 'BEFORE', $this, 'parserDependencies');
    }

    /** Register modules separately so DokuWiki tracks every file as a cache dependency. */
    public function registerScripts(Event $event, $param)
    {
        $entry = DOKU_PLUGIN . 'calculator/script.js';
        $position = array_search($entry, $event->data, true);
        if ($position === false) return;
        array_splice($event->data, $position, 0, [
            DOKU_PLUGIN . 'calculator/modules/engine.js',
            DOKU_PLUGIN . 'calculator/modules/regex.js',
            DOKU_PLUGIN . 'calculator/modules/user.js',
            DOKU_PLUGIN . 'calculator/modules/chem-weights.js',
            DOKU_PLUGIN . 'calculator/modules/chem-engine.js',
            DOKU_PLUGIN . 'calculator/modules/chem.js',
        ]);
    }

    public function parserDependencies(Event $event, $param)
    {
        $cache = $event->data;
        $cache->depends['files'] = array_merge($cache->depends['files'] ?? [], [
            __DIR__ . '/syntax/definition.php', __DIR__ . '/syntax/constants.php', __DIR__ . '/syntax/user.php', __DIR__ . '/syntax/chem.php', __DIR__ . '/inc/DefinitionParser.php',
            __DIR__ . '/inc/ExpressionValidator.php', __DIR__ . '/helper.php',
            __DIR__ . '/lang/en/lang.php', __DIR__ . '/lang/de/lang.php',
        ]);
    }

    public function definitionRequest(Event $event, $param)
    {
        if ($event->data !== 'plugin_calculator_definition') return;
        $event->preventDefault();
        $event->stopPropagation();
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        global $INPUT;
        try {
            $helper = $this->loadHelper('calculator');
            $definition = $helper->definition($INPUT->str('name'), $INPUT->str('page'));
            echo json_encode(['ok' => true, 'definition' => $definition], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        } catch (RuntimeException $e) {
            http_response_code($e->getCode() === 403 ? 403 : 400);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }
}
