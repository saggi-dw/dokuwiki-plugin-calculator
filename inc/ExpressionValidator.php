<?php
namespace CalculatorPlugin;

/** Syntax and variable validation only; no expression execution. GPL-2.0. */
class ExpressionValidator
{
    private $tokens = [];
    private $position = 0;
    private $depth = 0;
    private $variables;
    private $lists;
    private $references = [];
    private const FUNCTIONS = [
        'abs' => [1, 1], 'sqrt' => [1, 1], 'floor' => [1, 1],
        'ceil' => [1, 1], 'round' => [1, 1], 'min' => [2, 16],
        'max' => [2, 16], 'pow' => [2, 2],
    ];

    public static function validate($expression, array $variables, array $lists = [])
    {
        $parser = new self();
        $parser->variables = $variables;
        $parser->lists = $lists;
        if (strlen($expression) > 512) throw new DefinitionException('definition_expression_syntax');
        $offset = 0;
        while ($offset < strlen($expression)) {
            $rest = substr($expression, $offset);
            if (preg_match('/^\s/', $rest)) { $offset++; continue; }
            if (preg_match('/^(?:[0-9]+(?:\.[0-9]*)?|\.[0-9]+)/', $rest, $m)) {
                if (!is_finite((float)$m[0])) throw new DefinitionException('definition_number_too_large');
                $parser->tokens[] = ['type' => 'number', 'value' => $m[0]];
            } elseif (preg_match('/^(?:global\.[a-z][a-z0-9_]*|[a-z][a-z0-9_]*)/', $rest, $m)) {
                $parser->tokens[] = ['type' => 'name', 'value' => $m[0]];
            } elseif (preg_match('/^[+\-*\/%^(),]/', $rest, $m)) {
                $parser->tokens[] = ['type' => $m[0]];
            } else {
                throw new DefinitionException('definition_expression_syntax');
            }
            $offset += strlen($m[0]);
            if (count($parser->tokens) > 256) throw new DefinitionException('definition_expression_syntax');
        }
        $parser->guard('sum');
        if ($parser->position !== count($parser->tokens)) throw new DefinitionException('definition_expression_syntax');
        return array_keys($parser->references);
    }

    private function is($type)
    {
        return isset($this->tokens[$this->position]) && $this->tokens[$this->position]['type'] === $type;
    }

    private function expect($type)
    {
        if (!$this->is($type)) throw new DefinitionException('definition_expression_syntax');
        $this->position++;
    }

    private function guard($method)
    {
        if (++$this->depth > 32) throw new DefinitionException('definition_expression_syntax');
        $this->$method();
        $this->depth--;
    }

    private function primary()
    {
        if ($this->is('number')) { $this->position++; return; }
        if ($this->is('name')) {
            $name = $this->tokens[$this->position++]['value'];
            if ($this->is('(')) {
                $list = $this->tokens[$this->position + 1] ?? null;
                $close = $this->tokens[$this->position + 2] ?? null;
                if (in_array($name, ['sum', 'count', 'avg', 'min', 'max'], true)
                    && $list && $list['type'] === 'name' && in_array($list['value'], $this->lists, true)
                    && $close && $close['type'] === ')') {
                    $this->references[$list['value']] = true;
                    $this->position += 3;
                    return;
                }
                if (!isset(self::FUNCTIONS[$name])) throw new DefinitionException('definition_unknown_function', [$name]);
                $this->position++;
                $arguments = 0;
                if (!$this->is(')')) {
                    $this->guard('sum'); $arguments++;
                    while ($this->is(',')) { $this->position++; $this->guard('sum'); $arguments++; }
                }
                $this->expect(')');
                if ($arguments < self::FUNCTIONS[$name][0] || $arguments > self::FUNCTIONS[$name][1]) throw new DefinitionException('definition_function_arguments', [$name, self::FUNCTIONS[$name][0], self::FUNCTIONS[$name][1]]);
            } else {
                if (!in_array($name, $this->variables, true)) throw new DefinitionException('definition_unknown_variable', [$name]);
                $this->references[$name] = true;
            }
            return;
        }
        if ($this->is('(')) { $this->position++; $this->guard('sum'); $this->expect(')'); return; }
        throw new DefinitionException('definition_expression_syntax');
    }

    private function power()
    {
        $this->primary();
        if ($this->is('^')) { $this->position++; $this->guard('unary'); }
    }

    private function unary()
    {
        if ($this->is('+') || $this->is('-')) { $this->position++; $this->guard('unary'); }
        else $this->power();
    }

    private function product()
    {
        $this->unary();
        while ($this->is('*') || $this->is('/') || $this->is('%')) { $this->position++; $this->unary(); }
    }

    private function sum()
    {
        $this->product();
        while ($this->is('+') || $this->is('-')) { $this->position++; $this->product(); }
    }
}
