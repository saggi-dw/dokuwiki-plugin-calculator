# Calculator tests

The PHP files ending in `.test.php` are PHPUnit tests in the `plugin_calculator`
group. Run them through DokuWiki's `_test/phpunit.xml`, as described in the main
README. `integration.test.php` uses the real `DokuWikiTest` environment. The other
parser and definition tests extend PHPUnit's TestCase without replacing DokuWiki.

`struct.test.php` invokes the files in `isolated/` as child PHP processes because
they deliberately define stand-in DokuWiki and Struct classes. The `.check.php`
suffix keeps these standalone files out of DokuWiki's PHPUnit test discovery.

The JavaScript tests use Node's built-in test runner:

```sh
node --test _test/*.test.js
```

Run that command from the plugin directory. No npm dependencies are needed.

JSON fixtures and the text definitions in `fixtures/` are shared test contracts.
PHP tests compare the current parser output with the committed JSON; they do not
rewrite it. JavaScript tests consume the same files, so both jobs can run
independently. Update fixtures explicitly when intentionally changing the contract.
The Struct parser fixture records upstream parser outputs; it does not replace
Struct's installed parser at runtime.
