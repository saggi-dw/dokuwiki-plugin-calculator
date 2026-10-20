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
        max: [2, 16, function () { return Math.max.apply(null, arguments); }], pow: [2, 2, Math.pow]
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
            else if ((match = /^(?:global\.[a-z][a-z0-9_]*|[a-z][a-z0-9_]*)/.exec(rest))) tokens.push({ type: 'name', value: match[0] });
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
        function evaluate(node, values) {
            var v, a, b;
            if (node.op === 'number') v = node.value;
            else if (node.op === 'variable') v = values[node.name];
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
            else if (node.op === 'call') {
                var args = node.args.map(function (n) { return evaluate(n, values); });
                v = args.indexOf(null) >= 0 ? null : functions[node.name][2].apply(null, args);
            }
            else if (node.op === 'unary+') v = evaluate(node.right, values);
            else if (node.op === 'unary-') { v = evaluate(node.right, values); if (v !== null) v = -v; }
            else {
                a = evaluate(node.left, values); b = evaluate(node.right, values);
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
        var run = function (values) { return evaluate(tree, values); };
        run.references = Object.keys(references);
        return run;
    }
    function checkRange(n, field) {
        if ((field.min !== undefined && n < field.min) || (field.max !== undefined && n > field.max)) throw error('range_error', (field.min === undefined ? '−∞' : field.min) + ' … ' + (field.max === undefined ? '∞' : field.max));
        return n;
    }
    function calculate(def, raw, prepared) {
        var values = Object.create(null), lists = Object.create(null), textRows = Object.create(null);
        prepared = prepared || {};
        (def.constants || []).forEach(function (constant) {
            if (constant.scope === 'global') values[constant.name] = constant.value;
        });
        def.inputs.forEach(function (field) {
            if (field.type === 'const') { values[field.name] = field.value; }
            else if (field.type === 'decimal') {
                try { values[field.name] = checkRange(number(raw[field.name] || ''), field); }
                catch (e) { e.field = field.label; throw e; }
            } else if (field.type === 'regex-list') {
                var rows = prepared[field.name];
                if (!rows) throw error('regex_not_ready');
                textRows[field.name] = rows;
                lists[field.name] = rows.map(function (row, i) {
                    if (row.error) { var bad = error(row.error); bad.line = i + 1; bad.field = field.label; throw bad; }
                    if (row.raw === undefined) return null;
                    try { return checkRange(number(row.raw), field); }
                    catch (e) { e.line = i + 1; e.field = field.label; throw e; }
                });
            } else {
                var text = String(raw[field.name] || '').replace(/\r\n?/g, '\n');
                var lines = text.split('\n');
                if (text.length > 200000 || lines.length > 10000) throw error('too_many_lines');
                lists[field.name] = lines.map(function (line, i) {
                    if (!line.trim()) return null;
                    try { return checkRange(number(line), field); }
                    catch (e) { e.line = i + 1; e.field = field.label; throw e; }
                });
            }
        });
        var scalarNames = Object.keys(values), listNames = Object.keys(lists);
        var plans = Object.create(null), results = Object.create(null), state = Object.create(null);
        function isList(output) {
            return output.type === 'decimal-list' || output.type === 'text-list' || (!output.type && !!output.source);
        }
        listNames.forEach(function (name) { values[name] = lists[name]; });
        def.outputs.forEach(function (output) {
            if (typeof output.name !== 'string' || scalarNames.indexOf(output.name) >= 0 || listNames.indexOf(output.name) >= 0) throw error('invalid_expression');
            (isList(output) ? listNames : scalarNames).push(output.name);
        });
        def.outputs.forEach(function (output) {
            try {
                plans[output.name] = {output: output, run: compile(output.formula, (isList(output) ? ['value'] : []).concat(scalarNames), listNames)};
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
                    try { return plan.run(values); }
                    catch (e) { e.line = i + 1; e.field = output.label; throw e; }
                });
                values[name] = computed;
                result = {output: output, values: computed, rows: output.type === 'text-list' ? textRows[output.source] : undefined};
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
        if (!result.rows) return format(result.values, result.output.decimals, locale, result.output.fixed);
        var formatter = new Intl.NumberFormat(locale || 'de-DE', { useGrouping: false, minimumFractionDigits: result.output.fixed === true ? result.output.decimals : 0, maximumFractionDigits: result.output.decimals });
        return result.rows.map(function (row, i) {
            var value = result.values[i];
            if (value === null) return row.text;
            return row.text.slice(0, row.start) + formatter.format(Object.is(value, -0) ? 0 : value) + row.text.slice(row.end);
        }).join('\n');
    }
    api.engine = { number: number, compile: compile, calculate: calculate, format: format, formatResult: formatResult };
    if (typeof module !== 'undefined' && module.exports) module.exports = api.engine;
}(typeof window !== 'undefined' ? window : globalThis));
