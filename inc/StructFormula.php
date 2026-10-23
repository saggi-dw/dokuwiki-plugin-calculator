<?php
namespace CalculatorPlugin;
use dokuwiki\plugin\struct\meta\InlineConfigParser;

/** Optional Struct page-data integration through its public helper API. GPL-2.0. */
class StructFormula
{
    public static function isReference($formula)
    {
        return strpos($formula, '{{$') !== false;
    }

    public static function resolve($formula, $page, $revision = 0)
    {
        if (substr($formula, 0, 3) !== '{{$' || substr($formula, -2) !== '}}' || strlen($formula) > 2000) {
            throw new \RuntimeException('chem_struct_reference');
        }
        if ($page === '' || auth_quickaclcheck($page) < AUTH_READ) {
            throw new \RuntimeException('chem_struct_access');
        }
        $helper = plugin_load('helper', 'struct');
        if (!$helper || !is_callable([$helper, 'getData']) || !class_exists(InlineConfigParser::class)) {
            throw new \RuntimeException('chem_struct_missing');
        }
        try {
            $parser = new InlineConfigParser(substr($formula, 3, -2));
            $config = $parser->getConfig();
        } catch (\Throwable $e) {
            throw new \RuntimeException('chem_struct_reference');
        }
        // This adapter reads one field on the current page. Never silently drop a
        // Struct page selector or filter and read unrelated current-page data.
        if (count($config['schemas'] ?? []) !== 1 || count($config['cols'] ?? []) !== 1 ||
            ($config['schemas'][0][1] ?? '') !== '' ||
            ($config['filter'] ?? []) !== [['%pageid%', '=', '$ID$', 'AND']]) {
            throw new \RuntimeException('chem_struct_unsupported');
        }
        $schema = $config['schemas'][0][0];
        $field = $config['cols'][0];
        if (!is_string($schema) || $schema === '' || !is_string($field) || $field === '') {
            throw new \RuntimeException('chem_struct_reference');
        }
        try {
            $data = $helper->getData($page, $schema, max(0, (int)$revision));
        } catch (\Throwable $e) {
            // Do not expose database, filesystem or plugin exception details.
            throw new \RuntimeException('chem_struct_read_error');
        }
        $value = $data[$schema][$field] ?? null;
        if (!is_string($value) || trim($value) === '' || strlen($value) > 2000) {
            throw new \RuntimeException('chem_struct_value');
        }
        return trim($value);
    }
}
