<?php
use dokuwiki\Extension\Plugin;
require_once __DIR__ . '/inc/DefinitionParser.php';
/** @license GPL 2 @author saggi <saggi@gmx.de> */
class helper_plugin_calculator extends Plugin
{
    public function definition($name, $page)
    {
        if (!preg_match('/^[a-z][a-z0-9_-]{0,63}$/D', $name)) throw new InvalidArgumentException($this->getLang('invalid_name'));
        $page = cleanID($page);
        if ($page === '' || auth_quickaclcheck($page) < AUTH_READ) throw new RuntimeException($this->getLang('access_denied'), 403);
        $source = cleanID($this->getConf('definitions_page'));
        if ($source === '') throw new RuntimeException($this->getLang('missing_page'));
        if ($this->getConf('require_definition_read') && auth_quickaclcheck($source) < AUTH_READ) throw new RuntimeException($this->getLang('access_denied'), 403);
        if (!page_exists($source)) throw new RuntimeException($this->getLang('missing_page'));
        try {
            $text = rawWiki($source);
            $globals = \CalculatorPlugin\DefinitionParser::globalConstants($text);
            $catalog = \CalculatorPlugin\DefinitionParser::catalog($text);
            if (!array_key_exists($name, $catalog)) throw new InvalidArgumentException($this->getLang('missing_definition') . ' ' . $name);
            return \CalculatorPlugin\DefinitionParser::parse($name, $catalog[$name], $globals);
        } catch (\CalculatorPlugin\DefinitionException $e) {
            throw new InvalidArgumentException($this->definitionError($e), 0, $e);
        }
    }

    public function currentPageConstants()
    {
        global $ID, $INPUT, $ACT;
        $text = ($ACT === 'preview' && $INPUT->post->has('wikitext'))
            ? $INPUT->post->str('wikitext') : rawWiki($ID);
        return \CalculatorPlugin\DefinitionParser::globalConstants($text);
    }

    public function definitionError(\CalculatorPlugin\DefinitionException $e)
    {
        $message = vsprintf($this->getLang($e->getLanguageKey()), $e->getParameters());
        if ($e->getDefinitionLine() !== null) {
            $message = sprintf($this->getLang('definition_line'), $e->getDefinitionLine(), $message);
        }
        return $message;
    }

}
