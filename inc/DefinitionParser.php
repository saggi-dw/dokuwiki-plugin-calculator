<?php
/** Declarative calculator definitions. No PHP or JavaScript is executed. GPL-2.0. */
namespace CalculatorPlugin;
require_once __DIR__ . "/ExpressionValidator.php";

/** Structured, language-independent error, translated by the DokuWiki helper. */
class DefinitionException extends \InvalidArgumentException
{
    private $languageKey;
    private $parameters;
    private $definitionLine;

    public function __construct($languageKey, array $parameters = [], $definitionLine = null)
    {
        parent::__construct($languageKey);
        $this->languageKey = $languageKey;
        $this->parameters = $parameters;
        $this->definitionLine = $definitionLine;
    }

    public function getLanguageKey() { return $this->languageKey; }
    public function getParameters() { return $this->parameters; }
    public function getDefinitionLine() { return $this->definitionLine; }
}

class DefinitionParser
{
    public static function catalog($text)
    {
        if (strlen($text) > 1048576) throw new DefinitionException('definition_page_too_large');
        preg_match_all('/<calcdefinition[ \t]+([a-z][a-z0-9_-]{0,63})[ \t]*>([\s\S]*?)<\/calcdefinition>/', $text, $matches, PREG_SET_ORDER);
        $result = [];
        foreach ($matches as $match) {
            $name = $match[1];
            if (array_key_exists($name, $result)) throw new DefinitionException('definition_duplicate_name', [$name]);
            // Parse requested definitions only, so a broken neighbour does not break all calculators.
            $result[$name] = $match[2];
        }
        if (count($result) > 64) throw new DefinitionException('definition_too_many', [64]);
        return $result;
    }

    public static function globalConstants($text)
    {
        if (strlen($text) > 1048576) throw new DefinitionException('definition_page_too_large');
        preg_match_all('/<calcconstants[ \t]*>([\s\S]*?)<\/calcconstants>/', $text, $blocks);
        if (count($blocks[1]) > 1) throw new DefinitionException('constants_multiple_blocks');
        if (!$blocks[1]) return [];
        return self::parseConstants($blocks[1][0]);
    }

    public static function parseConstants($body)
    {
        $constants = [];
        foreach (preg_split('/\r\n|\n|\r/', $body) as $index => $line) {
            if (trim($line) === '' || preg_match('/^\s*#/', $line)) continue;
            if (!preg_match('/^\s*([a-z][a-z0-9_]{0,63})\s*=\s*(.*?)\s*$/D', $line, $m)) self::fail($index, 'constants_invalid_line');
            if (array_key_exists($m[1], $constants)) self::fail($index, 'constants_duplicate_name', [$m[1]]);
            try { $constants[$m[1]] = self::number($m[2]); }
            catch (DefinitionException $e) { throw new DefinitionException($e->getLanguageKey(), $e->getParameters(), $index + 1); }
            if (count($constants) > 128) self::fail($index, 'constants_too_many', [128]);
        }
        return $constants;
    }

    public static function parse($name, $body, array $globals = [])
    {
        if (!preg_match('/^[a-z][a-z0-9_-]{0,63}$/D', $name)) throw new DefinitionException('invalid_name');
        $title = null;
        $showWork = null;
        $sections = [];
        $locations = [];
        $current = null;
        $lines = preg_split('/\r\n|\n|\r/', $body);
        for ($index = 0; $index < count($lines); $index++) {
            $line = $lines[$index];
            if (trim($line) === '' || preg_match('/^\s*#/', $line)) continue;
            if (preg_match('/^title\s*=\s*(.+)$/u', $line, $m)) {
                if ($title !== null) self::fail($index, 'definition_duplicate_title');
                $title = trim($m[1]);
                $current = null;
            } elseif (preg_match('/^showwork\s*=\s*(.*?)\s*$/D', $line, $m)) {
                if ($showWork !== null) self::fail($index, 'definition_duplicate_property', ['showwork']);
                if (!in_array($m[1], ['true', 'false'], true)) self::fail($index, 'definition_invalid_boolean', ['showwork']);
                $showWork = $m[1] === 'true';
                $current = null;
            } elseif (preg_match('/^(input|output)[ \t]+([a-z][a-z0-9_]{0,63}):\s*$/', $line, $m)) {
                if (isset($sections[$m[2]]) || $m[2] === 'value') self::fail($index, 'definition_reserved_field', [$m[2]]);
                $current = $m[2];
                $sections[$current] = ['kind' => $m[1], 'name' => $current];
                $locations[$current] = ['section' => $index + 1];
            } elseif (preg_match('/^[ \t]+([a-z]+)\s*=\s*(.*?)\s*$/u', $line, $m) && $current !== null) {
                if (array_key_exists($m[1], $sections[$current])) self::fail($index, 'definition_duplicate_property', [$m[1]]);
                $propertyLine = $index + 1;
                $value = $m[2];
                if ($m[1] === 'default' && substr($value, 0, 1) === '"') {
                    // Capture the original line to preserve whitespace inside the quoted text.
                    preg_match('/^[ \t]+default\s*=\s*(.*)$/u', $line, $rawDefault);
                    $value = self::quotedDefault($lines, $index, substr($rawDefault[1], 1), $propertyLine);
                }
                $sections[$current][$m[1]] = $value;
                $locations[$current][$m[1]] = $propertyLine;
            } else {
                self::fail($index, 'definition_invalid_line');
            }
        }
        if (!$title || strlen($title) > 200) throw new DefinitionException('definition_invalid_title', [200]);
        if (count($sections) > 40) throw new DefinitionException('definition_too_many_fields', [40]);
        $inputs = [];
        $outputs = [];
        $lists = [];
        foreach ($sections as $section) {
            try {
                $allowed = $section['kind'] === 'input'
                    ? ['kind', 'name', 'type', 'label', 'unit', 'default', 'min', 'max', 'break']
                    : ['kind', 'name', 'type', 'label', 'unit', 'source', 'formula', 'decimals', 'fixed', 'break', 'template'];
                $isConstant = $section['kind'] === 'input' && ($section['type'] ?? '') === 'const';
                if ($isConstant) {
                    $allowed = ['kind', 'name', 'type', 'label', 'unit', 'value'];
                    if (!isset($section['label'])) $section['label'] = $section['name'];
                }
                $isRegex = $section['kind'] === 'input' && ($section['type'] ?? '') === 'regex-list';
                if ($isRegex) $allowed = ['kind', 'name', 'type', 'label', 'unit', 'pattern', 'flags', 'default', 'min', 'max', 'break'];
                if (array_diff(array_keys($section), $allowed)) throw new DefinitionException('definition_unknown_property', [$section['name']]);
                if (isset($section['break'])) {
                    if (!in_array($section['break'], ['true', 'false'], true)) throw new DefinitionException('definition_invalid_boolean', ['break']);
                    $section['break'] = $section['break'] === 'true';
                }
                if (empty($section['label']) || strlen($section['label']) > 200) throw new DefinitionException('definition_invalid_label', [$section['name'], 200]);
                if (isset($section['unit']) && strlen($section['unit']) > 40) throw new DefinitionException('definition_unit_too_long', [40]);
                if ($section['kind'] === 'input') {
                    if (!isset($section['type']) || !in_array($section['type'], ['decimal', 'decimal-list', 'regex-list', 'const'], true)) throw new DefinitionException('definition_invalid_type');
                    if ($isConstant) {
                        if (!isset($section['value'])) throw new DefinitionException('constants_value_required', [$section['name']]);
                        $section['value'] = self::number($section['value']);
                    }
                    if ($isRegex) {
                        if (!isset($section['pattern']) || $section['pattern'] === '' || strlen($section['pattern']) > 512 || preg_match('/[\x00-\x1f]/', $section['pattern'])) throw new DefinitionException('regex_invalid_pattern');
                        $section['flags'] = $section['flags'] ?? 'u';
                        if (!preg_match('/^(?:i?u?|ui)$/D', $section['flags'])) throw new DefinitionException('regex_invalid_flags');
                        $captures = [];
                        if (@preg_match(chr(1) . '(?:' . $section['pattern'] . ')|(?:)' . chr(1) . $section['flags'], '', $captures, PREG_UNMATCHED_AS_NULL) === false) throw new DefinitionException('regex_invalid_pattern');
                        $groupNames = array_values(array_filter(array_keys($captures), 'is_string'));
                        if (!$groupNames) throw new DefinitionException('regex_named_group_required');
                        $section['groups'] = [];
                        foreach ($groupNames as $groupName) {
                            if (preg_match('/^[a-z][a-z0-9_]{0,63}$/D', $groupName)) $section['groups'][] = $groupName;
                            elseif (!in_array('value', $groupNames, true)) throw new DefinitionException('regex_invalid_group_name', [$groupName]);
                        }
                    }
                    foreach (['min', 'max'] as $key) {
                        if (isset($section[$key])) $section[$key] = self::number($section[$key]);
                    }
                    if (isset($section['min'], $section['max']) && $section['min'] > $section['max']) throw new DefinitionException('definition_invalid_range');
                    if (isset($section['default'])) {
                        $isList = in_array($section['type'], ['decimal-list', 'regex-list'], true);
                        if ($isList && (strlen($section['default']) > 200000 || count(explode("\n", $section['default'])) > 10000)) throw new DefinitionException('definition_default_too_large', [200000, 10000]);
                        if (!$isList || $section['type'] === 'decimal-list') {
                            $defaults = $isList ? explode("\n", $section['default']) : [$section['default']];
                            foreach ($defaults as $default) {
                                if ($isList && trim($default) === '') continue;
                                try { $value = self::number($isList ? trim($default) : $default); }
                                catch (DefinitionException $e) { throw new DefinitionException($e->getLanguageKey(), $e->getParameters(), $locations[$section['name']]['default']); }
                                if ((isset($section['min']) && $value < $section['min']) || (isset($section['max']) && $value > $section['max'])) throw new DefinitionException('definition_default_out_of_range');
                                if (!$isList) $section['default'] = $value;
                            }
                        }
                    }
                    if (in_array($section['type'], ['decimal-list', 'regex-list'], true)) $lists[] = $section['name'];
                    $inputs[] = $section;
                } else {
                    if (!isset($section['type'])) $section['type'] = isset($section['source']) ? 'decimal-list' : 'decimal';
                    if (!in_array($section['type'], ['decimal', 'decimal-list', 'text-list'], true)) throw new DefinitionException('definition_invalid_output_type');
                    if ($section['type'] === 'decimal' && isset($section['source'])) throw new DefinitionException('definition_scalar_source');
                    if (!isset($section['formula']) || $section['formula'] === '' || strlen($section['formula']) > 512) throw new DefinitionException('definition_invalid_formula', [512]);
                    if (!preg_match('/^[a-zA-Z0-9_+*\/().,%^\s-]+$/D', $section['formula'])) throw new DefinitionException('definition_formula_character');
                    $digits = isset($section['decimals']) ? $section['decimals'] : '3';
                    if (!preg_match('/^(?:[0-9]|1[0-2])$/D', $digits)) throw new DefinitionException('definition_invalid_decimals', [0, 12]);
                    $section['decimals'] = (int)$digits;
                    if (isset($section['fixed']) && !in_array($section['fixed'], ['true', 'false'], true)) throw new DefinitionException('definition_invalid_boolean', ['fixed']);
                    $section['fixed'] = ($section['fixed'] ?? 'false') === 'true';
                    if (isset($section['template'])) {
                        $template = $section['template'];
                        $literal = preg_replace('/\{(?:line|label|value|unit)\}/', '', $template);
                        if ($template === '' || strlen($template) > 2000 || preg_match('/[\r\n]/', $template) || strpbrk($literal, '{}') !== false) throw new DefinitionException('definition_invalid_template', [2000]);
                        if ($section['type'] === 'decimal' && strpos($template, '{line}') !== false) throw new DefinitionException('definition_template_requires_list');
                    }
                    $outputs[] = $section;
                }
            } catch (DefinitionException $e) {
                $properties = [
                    'definition_invalid_boolean' => 'break', 'regex_invalid_pattern' => 'pattern', 'regex_invalid_flags' => 'flags', 'regex_named_group_required' => 'pattern', 'regex_invalid_group_name' => 'pattern',
                    'constants_value_required' => 'value', 'definition_invalid_label' => 'label', 'definition_unit_too_long' => 'unit',
                    'definition_invalid_type' => 'type', 'definition_invalid_output_type' => 'type',
                    'definition_invalid_range' => 'min', 'definition_default_out_of_range' => 'default', 'definition_default_too_large' => 'default',
                    'definition_scalar_source' => 'source', 'definition_invalid_decimals' => 'decimals',
                    'definition_invalid_template' => 'template', 'definition_template_requires_list' => 'template',
                    'definition_invalid_formula' => 'formula', 'definition_formula_character' => 'formula',
                ];
                $property = $e->getLanguageKey() === 'definition_invalid_boolean'
                    ? $e->getParameters()[0] : ($properties[$e->getLanguageKey()] ?? 'section');
                $line = $locations[$section['name']][$property] ?? $locations[$section['name']]['section'];
                throw new DefinitionException($e->getLanguageKey(), $e->getParameters(), $e->getDefinitionLine() ?? $line);
            }
        }
        if (count($lists) > 1 || !$outputs || count($inputs) > 32 || count($outputs) > 8) throw new DefinitionException('definition_invalid_structure', [1, 8, 32]);
        $variables = [];
        foreach ($inputs as $input) {
            if (in_array($input['type'], ['decimal', 'const'], true)) $variables[] = $input['name'];
        }
        foreach ($globals as $globalName => $globalValue) $variables[] = 'global.' . $globalName;
        // Normalize output types before making their names available to expressions.
        $inputFields = array_column($inputs, null, 'name');
        $allLists = array_values(array_filter($lists, function ($name) use ($inputFields) {
            $input = $inputFields[$name];
            return $input['type'] !== 'regex-list' || in_array('value', $input['groups'], true);
        }));
        foreach ($outputs as &$output) {
            $source = $inputFields[$output['source'] ?? ''] ?? [];
            $sourceType = $source['type'] ?? '';
            if (!isset($sections[$output['name']]['type']) && $sourceType === 'regex-list' && in_array('value', $source['groups'], true)) $output['type'] = 'text-list';
            if ($output['type'] === 'decimal') $variables[] = $output['name'];
            else $allLists[] = $output['name'];
        }
        unset($output);
        $used = [];
        $dependencies = [];
        foreach ($outputs as $output) {
            try {
                $source = $inputFields[$output['source'] ?? ''] ?? [];
                $sourceType = $source['type'] ?? '';
                if (in_array($output['type'], ['decimal-list', 'text-list'], true) && (!isset($output['source']) || !$lists || $output['source'] !== $lists[0])) throw new DefinitionException('definition_invalid_source');
                if (($output['type'] === 'text-list' && $sourceType !== 'regex-list') || ($output['type'] === 'decimal-list' && !in_array($sourceType, ['decimal-list', 'regex-list'], true))) throw new DefinitionException('definition_invalid_source');
                if ($output['type'] === 'text-list' && !in_array('value', $source['groups'], true)) throw new DefinitionException('regex_text_requires_value');
                $rowVariables = [];
                if (in_array($output['type'], ['decimal-list', 'text-list'], true)) {
                    if ($sourceType !== 'regex-list' || in_array('value', $source['groups'], true)) $rowVariables[] = 'value';
                    if ($sourceType === 'regex-list') foreach ($source['groups'] as $groupName) $rowVariables[] = 'row.' . $groupName;
                }
                $references = ExpressionValidator::validate($output['formula'], array_merge($variables, $rowVariables), $allLists);
                $dependencies[$output['name']] = $references;
                foreach ($references as $reference) $used[$reference] = true;
            } catch (DefinitionException $e) {
                $line = $locations[$output['name']][$e->getLanguageKey() === 'definition_invalid_source' ? 'source' : 'formula'] ?? $locations[$output['name']]['section'];
                throw new DefinitionException($e->getLanguageKey(), $e->getParameters(), $e->getDefinitionLine() ?? $line);
            }
        }
        $state = [];
        $visit = function ($name, array $path) use (&$visit, &$state, $dependencies, $locations) {
            if (($state[$name] ?? 0) === 2) return;
            if (($state[$name] ?? 0) === 1) {
                $path[] = $name;
                throw new DefinitionException('definition_circular_reference', [implode(' -> ', $path)], $locations[$name]['formula']);
            }
            $state[$name] = 1;
            $path[] = $name;
            foreach ($dependencies[$name] as $reference) {
                if (isset($dependencies[$reference])) $visit($reference, $path);
            }
            $state[$name] = 2;
        };
        foreach (array_keys($dependencies) as $outputName) $visit($outputName, []);
        $constants = [];
        foreach ($inputs as $input) {
            if ($input['type'] === 'const' && isset($used[$input['name']])) $constants[] = [
                'name' => $input['name'], 'label' => $input['label'], 'unit' => $input['unit'] ?? '',
                'value' => $input['value'], 'scope' => 'local',
            ];
        }
        foreach ($globals as $globalName => $globalValue) {
            if (isset($used['global.' . $globalName])) $constants[] = [
                'name' => 'global.' . $globalName, 'label' => $globalName, 'unit' => '',
                'value' => $globalValue, 'scope' => 'global',
            ];
        }
        return ['name' => $name, 'title' => $title, 'inputs' => $inputs, 'outputs' => $outputs, 'constants' => $constants, 'showwork' => $showWork ?? false];
    }

    /** Literal quoted defaults: preserve newlines/tabs, decode only escaped quote/backslash. */
    private static function quotedDefault(array $lines, &$index, $segment, $propertyLine)
    {
        $text = '';
        while (true) {
            $length = strlen($segment);
            for ($at = 0; $at < $length; $at++) {
                $character = $segment[$at];
                if ($character === '\\' && $at + 1 < $length && in_array($segment[$at + 1], ['"', '\\'], true)) {
                    $text .= $segment[++$at];
                } elseif ($character === '"') {
                    if (trim(substr($segment, $at + 1)) !== '') throw new DefinitionException('definition_invalid_default_text', [], $propertyLine);
                    return $text;
                } else {
                    $text .= $character;
                }
            }
            if (++$index >= count($lines)) throw new DefinitionException('definition_invalid_default_text', [], $propertyLine);
            $text .= "\n";
            $segment = $lines[$index];
        }
    }

    private static function number($text)
    {
        if (!preg_match('/^[+-]?(?:[0-9]+(?:[.,][0-9]*)?|[.,][0-9]+)$/D', $text)) throw new DefinitionException('definition_invalid_number', [$text]);
        $value = (float)str_replace(',', '.', $text);
        if (!is_finite($value)) throw new DefinitionException('definition_number_too_large');
        return $value;
    }

    private static function fail($line, $key, array $parameters = [])
    {
        throw new DefinitionException($key, $parameters, $line + 1);
    }
}
