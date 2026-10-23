/* Calculator expression engine. GPL-2.0. No eval, Function or executable definitions. */
(function (root) {
    'use strict';
    var api = root.DokuCalculator = root.DokuCalculator || { modules: {} };
    function error(code, detail) {
        var e = new Error(code); e.code = code; e.detail = detail || ''; return e;
    }
    function number(text) {
        text = String(text).trim();
        if (!/^[+-]?(?:[0-9]+(?:[.,][0-9]*)?|[.,][0-9]+)$/.test(text)) throw error('invalid_number');
        var n = Number(text.replace(',', '.'));
        if (!Number.isFinite(n)) throw error('nonfinite');
        return n;
    }
    var functions = {
        abs: [1, 1, Math.abs], sqrt: [1, 1, Math.sqrt],
        floor: [1, 1, Math.floor], ceil: [1, 1, Math.ceil], round: [1, 1, Math.round],
        min: [2, 16, function () { return Math.min.apply(null, arguments); }],
        max: [2, 16, function () { return Math.max.apply(null, arguments); }], pow: [2, 2, Math.pow], if: [3, 3]
    };
    function compile(expression, variables, listNames) {
        listNames = listNames || [];
        var aggregateCache = new WeakMap(), references = Object.create(null);
        if (typeof expression !== 'string' || expression.length > 512) throw error('invalid_expression');
        var tokens = [], position = 0;
        while (position < expression.length) {
            var rest = expression.slice(position), match;
            if (/^\s/.test(rest)) { position++; continue; }
            if ((match = /^(?:[0-9]+(?:\.[0-9]*)?|\.[0-9]+)/.exec(rest))) tokens.push({ type: 'number', value: Number(match[0]) });
            else if ((match = /^(?:(?:global|row)\.[a-z][a-z0-9_]*|[a-z][a-z0-9_]*)/.exec(rest))) tokens.push({ type: 'name', value: match[0] });
            else if ((match = /^[+\-*/%^(),]/.exec(rest))) tokens.push({ type: match[0] });
            else throw error('invalid_expression');
            position += match[0].length;
            if (tokens.length > 256) throw error('invalid_expression');
        }
        var at = 0, depth = 0;
        function is(type) { return at < tokens.length && tokens[at].type === type; }
        function expect(type) { if (!is(type)) throw error('invalid_expression'); at++; }
        function guarded(parse) {
            depth++; if (depth > 32) throw error('invalid_expression');
            var result = parse(); depth--; return result;
        }
        function primary() {
            if (is('number')) return { op: 'number', value: tokens[at++].value };
            if (is('name')) {
                var name = tokens[at++].value;
                if (is('(')) {
                    var listToken = tokens[at + 1], closeToken = tokens[at + 2];
                    if (name === 'exists') {
                        if (!listToken || listToken.type !== 'name' || listToken.value.indexOf('row.') !== 0 || !closeToken || closeToken.type !== ')') throw error('invalid_expression', name);
                        if (variables.indexOf(listToken.value) < 0) throw error('unknown_variable', listToken.value);
                        at += 3;
                        references[listToken.value] = true;
                        return {op: 'exists', name: listToken.value};
                    }
                    if (['sum', 'count', 'avg', 'min', 'max'].indexOf(name) >= 0 &&
                        listToken && listToken.type === 'name' && listNames.indexOf(listToken.value) >= 0 &&
                        closeToken && closeToken.type === ')') {
                        at += 3;
                        references[listToken.value] = true;
                        return { op: 'aggregate', name: name, list: listToken.value };
                    }
                    if (!Object.prototype.hasOwnProperty.call(functions, name)) throw error('invalid_expression', name);
                    at++;
                    var args = [];
                    if (!is(')')) { args.push(guarded(sum)); while (is(',')) { at++; args.push(guarded(sum)); } }
                    expect(')');
                    if (args.length < functions[name][0] || args.length > functions[name][1]) throw error('invalid_expression', name);
                    return { op: 'call', name: name, args: args };
                }
                if (variables.indexOf(name) < 0) throw error('unknown_variable', name);
                references[name] = true;
                return { op: 'variable', name: name };
            }
            if (is('(')) { at++; var result = guarded(sum); expect(')'); return result; }
            throw error('invalid_expression');
        }
        function power() {
            var left = primary();
            if (is('^')) { at++; return { op: '^', left: left, right: guarded(unary) }; }
            return left;
        }
        function unary() {
            if (is('+') || is('-')) { var sign = tokens[at++].type; return { op: 'unary' + sign, right: guarded(unary) }; }
            return power();
        }
        function product() {
            var left = unary();
            while (is('*') || is('/') || is('%')) { var op = tokens[at++].type; left = { op: op, left: left, right: unary() }; }
            return left;
        }
        function sum() {
            var left = product();
            while (is('+') || is('-')) { var op = tokens[at++].type; left = { op: op, left: left, right: product() }; }
            return left;
        }
        var tree = guarded(sum);
        if (at !== tokens.length) throw error('invalid_expression');
        function evaluate(node, values, readRow, hasRow) {
            function visit(child) { return evaluate(child, values, readRow, hasRow); }
            var v, a, b;
            if (node.op === 'number') v = node.value;
            else if (node.op === 'variable') v = readRow && node.name.indexOf('row.') === 0 ? readRow(node.name) : values[node.name];
            else if (node.op === 'exists') v = (hasRow ? hasRow(node.name) : Object.prototype.hasOwnProperty.call(values, node.name) && values[node.name] !== undefined) ? 1 : 0;
            else if (node.op === 'aggregate') {
                var list = values[node.list], stats = aggregateCache.get(list);
                if (!stats) {
                    var count = 0, total = 0, minimum = null, maximum = null;
                    list.forEach(function (item) {
                        if (item === null) return;
                        if (!Number.isFinite(item)) throw error('nonfinite');
                        count++; total += item;
                        minimum = minimum === null ? item : Math.min(minimum, item);
                        maximum = maximum === null ? item : Math.max(maximum, item);
                    });
                    // Divide before summing for a mean that remains finite for large inputs.
                    var mean = count ? list.reduce(function (acc, item) { return item === null ? acc : acc + item / count; }, 0) : null;
                    stats = {sum: total, count: count, avg: mean, min: minimum, max: maximum};
                    aggregateCache.set(list, stats);
                }
                v = stats[node.name];
            }
            else if (node.op === 'call' && node.name === 'if') {
                a = visit(node.args[0]);
                v = a === null ? null : visit(node.args[a === 0 ? 2 : 1]);
            }
            else if (node.op === 'call') {
                var args = node.args.map(function (n) { return visit(n); });
                v = args.indexOf(null) >= 0 ? null : functions[node.name][2].apply(null, args);
            }
            else if (node.op === 'unary+') v = visit(node.right);
            else if (node.op === 'unary-') { v = visit(node.right); if (v !== null) v = -v; }
            else {
                a = visit(node.left); b = visit(node.right);
                if (a === null || b === null) return null;
                if ((node.op === '/' || node.op === '%') && b === 0) throw error('division_zero');
                switch (node.op) {
                    case '+': v = a + b; break; case '-': v = a - b; break;
                    case '*': v = a * b; break; case '/': v = a / b; break;
                    case '%': v = a % b; break; case '^': v = Math.pow(a, b); break;
                    default: throw error('invalid_expression');
                }
            }
            if (v === null) return null;
            if (!Number.isFinite(v)) throw error('nonfinite');
            return v;
        }
        var run = function (values, readRow, hasRow) { return evaluate(tree, values, readRow, hasRow); };
        run.references = Object.keys(references);
        return run;
    }
    function checkRange(n, field) {
        if ((field.min !== undefined && n < field.min) || (field.max !== undefined && n > field.max)) throw error('range_error', (field.min === undefined ? '−∞' : field.min) + ' … ' + (field.max === undefined ? '∞' : field.max));
        return n;
    }
    function calculate(def, raw, prepared) {
        var values = Object.create(null), lists = Object.create(null), textRows = Object.create(null);
        var inputFields = Object.create(null), rowCache = Object.create(null);
        prepared = prepared || {};
        (def.constants || []).forEach(function (constant) {
            if (constant.scope === 'global') values[constant.name] = constant.value;
        });
        def.inputs.forEach(function (field) {
            inputFields[field.name] = field;
            if (field.type === 'const') { values[field.name] = field.value; }
            else if (field.type === 'decimal') {
                try { values[field.name] = checkRange(number(raw[field.name] || ''), field); }
                catch (e) { e.field = field.label; throw e; }
            } else if (field.type === 'regex-list') {
                var rows = prepared[field.name];
                if (!rows) throw error('regex_not_ready');
                textRows[field.name] = rows;
                rowCache[field.name] = [];
                var hasValue = (field.groups || ['value']).indexOf('value') >= 0;
                lists[field.name] = rows.map(function (row, i) {
                    if (row.error) { var bad = error(row.error); bad.line = i + 1; bad.field = field.label; throw bad; }
                    if (!hasValue) return row.rawGroups === undefined ? null : 0;
                    if (row.raw === undefined) return null;
                    try { return checkRange(number(row.raw), field); }
                    catch (e) { e.line = i + 1; e.field = field.label; throw e; }
                });
            } else {
                var text = String(raw[field.name] || '').replace(/\r\n?/g, '\n');
                var lines = text.split('\n');
                textRows[field.name] = lines.map(function (line) { return {text: line}; });
                if (text.length > 200000 || lines.length > 10000) throw error('too_many_lines');
                lists[field.name] = lines.map(function (line, i) {
                    if (!line.trim()) return null;
                    try { return checkRange(number(line), field); }
                    catch (e) { e.line = i + 1; e.field = field.label; throw e; }
                });
            }
        });
        var scalarNames = Object.keys(values), listNames = Object.keys(lists).filter(function (name) {
            var field = inputFields[name];
            return field.type !== 'regex-list' || (field.groups || ['value']).indexOf('value') >= 0;
        });
        var plans = Object.create(null), results = Object.create(null), state = Object.create(null);
        function isList(output) {
            return output.type === 'decimal-list' || output.type === 'text-list' || (!output.type && !!output.source);
        }
        Object.keys(lists).forEach(function (name) { values[name] = lists[name]; });
        def.outputs.forEach(function (output) {
            if (typeof output.name !== 'string' || scalarNames.indexOf(output.name) >= 0 || listNames.indexOf(output.name) >= 0 || Object.prototype.hasOwnProperty.call(lists, output.name)) throw error('invalid_expression');
            (isList(output) ? listNames : scalarNames).push(output.name);
        });
        def.outputs.forEach(function (output) {
            try {
                var source = inputFields[output.source], rowVariables = [];
                if (isList(output)) {
                    if (!source || source.type !== 'regex-list' || (source.groups || ['value']).indexOf('value') >= 0) rowVariables.push('value');
                    if (source && source.type === 'regex-list') (source.groups || ['value']).forEach(function (name) { rowVariables.push('row.' + name); });
                }
                if (output.type === 'text-list' && source && source.type === 'regex-list' && (source.groups || ['value']).indexOf('value') < 0) throw error('regex_text_requires_value');
                var run = compile(output.formula, rowVariables.concat(scalarNames), listNames);
                plans[output.name] = {output: output, run: run};
            } catch (e) { e.field = output.label; throw e; }
        });
        function resolve(name, path) {
            if (state[name] === 2) return;
            var plan = plans[name], output = plan.output;
            if (state[name] === 1) {
                var cycle = error('circular_reference', path.concat(name).join(' -> '));
                cycle.field = output.label; throw cycle;
            }
            state[name] = 1;
            plan.run.references.forEach(function (reference) {
                if (Object.prototype.hasOwnProperty.call(plans, reference)) resolve(reference, path.concat(name));
            });
            var result;
            if (!isList(output)) {
                try { values[name] = plan.run(values); }
                catch (e) { e.field = output.label; throw e; }
                result = {output: output, values: [values[name]]};
            } else {
                var computed = lists[output.source].map(function (value, i) {
                    if (value === null) return null;
                    values.value = value;
                    var source = inputFields[output.source], row = textRows[output.source] && textRows[output.source][i];
                    function rawGroup(reference) {
                        var group = reference.slice(4);
                        return row && (row.rawGroups ? row.rawGroups[group] : group === 'value' ? row.raw : undefined);
                    }
                    function readRow(reference) {
                        var group = reference.slice(4);
                        var cache = rowCache[output.source][i] || (rowCache[output.source][i] = Object.create(null));
                        try {
                            if (!Object.prototype.hasOwnProperty.call(cache, group)) {
                                var raw = rawGroup(reference);
                                if (raw === undefined) throw error('regex_invalid_capture');
                                cache[group] = checkRange(number(raw), source);
                            }
                            return cache[group];
                        } catch (e) {
                            e.line = i + 1; e.field = source.label;
                            e.detail = reference + (e.detail ? ': ' + e.detail : ''); throw e;
                        }
                    }
                    try { return plan.run(values, readRow, function (reference) { return rawGroup(reference) !== undefined; }); }
                    catch (e) { e.line = i + 1; if (!e.field) e.field = output.label; throw e; }
                });
                values[name] = computed;
                result = {output: output, values: computed, rows: output.type === 'text-list' || output.template !== undefined ? textRows[output.source] : undefined};
            }
            results[name] = result;
            state[name] = 2;
        }
        def.outputs.forEach(function (output) { resolve(output.name, []); });
        // Display order follows the definition, independently of calculation order.
        return def.outputs.map(function (output) { return results[output.name]; });
    }

    function format(values, decimals, locale, fixed) {
        var formatter = new Intl.NumberFormat(locale || 'de-DE', { useGrouping: false, minimumFractionDigits: fixed === true ? decimals : 0, maximumFractionDigits: decimals });
        return values.map(function (v) { return v === null ? '' : formatter.format(Object.is(v, -0) ? 0 : v); }).join('\n');
    }
    function formatResult(result, locale) {
        var template = result.output.template;
        if (template === undefined && !result.rows) return format(result.values, result.output.decimals, locale, result.output.fixed);
        var formatter = new Intl.NumberFormat(locale || 'de-DE', { useGrouping: false, minimumFractionDigits: result.output.fixed === true ? result.output.decimals : 0, maximumFractionDigits: result.output.decimals });
        if (template !== undefined) {
            if (typeof template !== 'string' || !template.length || new TextEncoder().encode(template).length > 2000 || /[\r\n]/.test(template) ||
                /[{}]/.test(template.replace(/\{(?:line|label|value|unit)\}/g, '')) ||
                (!result.rows && template.indexOf('{line}') >= 0)) throw error('invalid_template');
            return result.values.map(function (value, i) {
                var line = result.rows ? result.rows[i].text : '';
                if (value === null) return line;
                var fields = {
                    line: line, label: result.output.label || '', unit: result.output.unit || '',
                    value: formatter.format(Object.is(value, -0) ? 0 : value)
                };
                // Replace once: inserted source text and labels never become new placeholders.
                return template.replace(/\{(line|label|value|unit)\}/g, function (_, name) { return fields[name]; });
            }).join('\n');
        }
        return result.rows.map(function (row, i) {
            var value = result.values[i];
            if (value === null) return row.text;
            return row.text.slice(0, row.start) + formatter.format(Object.is(value, -0) ? 0 : value) + row.text.slice(row.end);
        }).join('\n');
    }
    api.engine = { number: number, compile: compile, calculate: calculate, format: format, formatResult: formatResult };
    if (typeof module !== 'undefined' && module.exports) module.exports = api.engine;
}(typeof window !== 'undefined' ? window : globalThis));
