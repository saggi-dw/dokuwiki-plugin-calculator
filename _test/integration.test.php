<?php
/**
 * Integration checks against the real DokuWiki parser, renderer and helper.
 * @group plugin_calculator
 */
class CalculatorIntegrationTest extends DokuWikiTest
{
    protected $pluginsEnabled = ['calculator'];

    public function setUp(): void
    {
        parent::setUp();
        global $ID, $REV, $ACT, $conf;
        $ID = 'calculator:example';
        $REV = 0;
        $ACT = 'show';
        $_SESSION = [];
        $conf['lang'] = 'en';
        $conf['plugin']['calculator']['definitions_page'] = 'calculator:definitions';
        $conf['plugin']['calculator']['require_definition_read'] = 1;
    }

    private function renderWiki($text): array
    {
        $info = [];
        $html = p_render('xhtml', p_get_instructions($text), $info);
        return [$html, $info];
    }

    private function saveDefinition($page = 'calculator:definitions'): void
    {
        saveWikiText($page, file_get_contents(__DIR__.'/fixtures/dilution.txt'), 'Calculator test fixture');
    }

    public function testFixedAndInteractiveChemistryAreRecognized(): void
    {
        [$html, $info] = $this->renderWiki('<calcchem C32H16CuN8>');
        $this->assertStringContainsString('data-calculator="chem"', $html);
        $this->assertStringContainsString('data-formula="C32H16CuN8"', $html);
        $this->assertNotFalse($info['cache'] ?? true);
        [$html] = $this->renderWiki('<calcchem>');
        $this->assertStringContainsString('data-formula=""', $html);
    }

    public function testStructIsOptionalAndMissingDependencyHasLocalizedError(): void
    {
        [$html, $info] = $this->renderWiki('<calcchem {{$ material.formula }}>');
        $plugin = plugin_load('syntax', 'calculator_chem');
        $this->assertStringContainsString(hsc($plugin->getLang('chem_struct_missing')), $html);
        $this->assertStringContainsString('role="alert"', $html);
        $this->assertFalse($info['cache']);
        $this->assertStringNotContainsString('data-calculator="chem"', $html);
    }

    public function testUserCalculatorIsOnlyAPageAwarePlaceholder(): void
    {
        [$html] = $this->renderWiki('<calcuser dilution>');
        $this->assertStringContainsString('data-calculator="user"', $html);
        $this->assertStringContainsString('data-name="dilution"', $html);
        $this->assertStringContainsString('data-page="calculator:example"', $html);
        $this->assertStringNotContainsString('initial * addition', $html);
    }

    public function testDefinitionPageShowsTitleWithoutFormulaSource(): void
    {
        $text = file_get_contents(__DIR__.'/fixtures/dilution.txt');
        [$html, $info] = $this->renderWiki($text);
        $this->assertStringContainsString('calculator-definition-title', $html);
        $this->assertStringNotContainsString('calculator-definition-error', $html);
        $this->assertStringNotContainsString('initial * addition / 100', $html);
        $this->assertFalse($info['cache']);
    }

    public function testInvalidDefinitionUsesDokuWikiLanguageAndErrorMarkup(): void
    {
        $text = str_replace('initial * addition / 100', 'unknown * addition', file_get_contents(__DIR__.'/fixtures/dilution.txt'));
        [$html] = $this->renderWiki($text);
        $helper = plugin_load('helper', 'calculator');
        $message = sprintf($helper->getLang('definition_unknown_variable'), 'unknown');
        $this->assertStringContainsString(hsc($message), $html);
        $this->assertStringContainsString('calculator-definition-error', $html);
    }

    public function testHelperReadsUpdatedDefinitionsInsteadOfEmbeddingThemInPageCache(): void
    {
        $this->saveDefinition();
        $helper = plugin_load('helper', 'calculator');
        $first = $helper->definition('dilution', 'calculator:example');
        $this->assertSame('initial * addition / 100', $first['outputs'][0]['formula']);
        $updated = str_replace('initial * addition / 100', 'initial + addition', file_get_contents(__DIR__.'/fixtures/dilution.txt'));
        saveWikiText('calculator:definitions', $updated, 'Updated calculation');
        $second = $helper->definition('dilution', 'calculator:example');
        $this->assertSame('initial + addition', $second['outputs'][0]['formula']);
    }

    public function testRestrictedDefinitionPageIsDenied(): void
    {
        global $conf;
        $this->saveDefinition('private:calculators');
        $conf['plugin']['calculator']['definitions_page'] = 'private:calculators';
        $helper = plugin_load('helper', 'calculator');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(403);
        $helper->definition('dilution', 'calculator:example');
    }

    public function testDisablingDefinitionAclStillProtectsTheEmbeddingPage(): void
    {
        global $conf;
        $this->saveDefinition('private:calculators');
        $conf['plugin']['calculator']['definitions_page'] = 'private:calculators';
        $conf['plugin']['calculator']['require_definition_read'] = 0;
        $helper = plugin_load('helper', 'calculator');
        $this->assertSame('dilution', $helper->definition('dilution', 'calculator:example')['name']);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(403);
        $helper->definition('dilution', 'private:page');
    }

    public function testAjaxReturnsJsonAndDisablesResponseCaching(): void
    {
        $this->saveDefinition();
        $response = (new TestRequest())->post(['call'=>'plugin_calculator_definition', 'name'=>'dilution', 'page'=>'calculator:example'], '/lib/exe/ajax.php');
        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertTrue($data['ok']);
        $this->assertSame('dilution', $data['definition']['name']);
        // CLI PHP exposes response headers only when Xdebug provides header capture.
        if (function_exists('xdebug_get_headers')) {
            $this->assertStringContainsString('no-store', $response->getHeader('Cache-Control'));
        }
    }

    public function testParserCacheTracksTheOptionalStructAdapter(): void
    {
        $action = plugin_load('action', 'calculator');
        $cache = new stdClass();
        $cache->depends = ['files'=>[]];
        $event = new \dokuwiki\Extension\Event('PARSER_CACHE_USE', $cache);
        $action->parserDependencies($event, null);
        $this->assertContains(dirname(__DIR__).'/inc/StructFormula.php', $cache->depends['files']);
    }
}
