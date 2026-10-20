/* Chemical formula parser and mass fractions. GPL-2.0. */
(function (root) {
    'use strict';
    var api = root.DokuCalculator = root.DokuCalculator || { modules: {} };
    var weights = api.atomicWeights;
    function error(code, position, detail) {
        var e = new Error(code); e.code = code; e.position = position + 1; e.detail = detail || ''; return e;
    }
    function calculate(input, part) {
        if (part === undefined) part = 100;
        if (!Number.isFinite(part) || part < 0 || part > 100) throw error('chem_part_error', 0);
        if (typeof input !== 'string' || !input.trim()) throw error('chem_enter_formula', 0);
        if (input.length > 2000) throw error('chem_too_long', 0);
        var formula = input.trim().replace(/[₀-₉]/g, function (c) { return String('₀₁₂₃₄₅₆₇₈₉'.indexOf(c)); });
        var at = 0;
        function separator(c) { return c === '·' || c === '*' || c === '.'; }
        function count() {
            var start = at;
            while (at < formula.length && /[0-9]/.test(formula[at])) at++;
            if (start === at) return 1;
            var n = Number(formula.slice(start, at));
            if (!Number.isSafeInteger(n) || n <= 0) throw error('chem_invalid_count', start);
            return n;
        }
        function add(target, symbol, number) {
            var value = (target[symbol] || 0) + number;
            if (!Number.isSafeInteger(number) || !Number.isSafeInteger(value)) throw error('chem_too_large', at);
            target[symbol] = value;
        }
        function merge(target, source, factor) {
            Object.keys(source).forEach(function (symbol) { add(target, symbol, source[symbol] * factor); });
        }
        function group(closing, depth) {
            if (depth > 32) throw error('chem_too_deep', at);
            var result = Object.create(null), terms = 0;
            while (at < formula.length) {
                var c = formula[at];
                if (closing && c === closing) break;
                if (!closing && separator(c)) break;
                if (c === '(' || c === '[' || c === '{') {
                    var expected = c === '(' ? ')' : c === '[' ? ']' : '}';
                    at++;
                    var nested = group(expected, depth + 1);
                    if (formula[at] !== expected) throw error('chem_missing_bracket', at, expected);
                    at++;
                    merge(result, nested, count());
                } else if (/[A-Z]/.test(c)) {
                    var start = at, symbol = c; at++;
                    if (at < formula.length && /[a-z]/.test(formula[at])) symbol += formula[at++];
                    if (!Object.prototype.hasOwnProperty.call(weights, symbol)) throw error('chem_unknown_element', start, symbol);
                    add(result, symbol, count());
                } else {
                    throw error('chem_invalid_character', at, c);
                }
                terms++;
            }
            if (!terms) throw error('chem_empty_group', at);
            return result;
        }
        var counts = Object.create(null);
        do {
            var factor = count();
            merge(counts, group('', 0), factor);
            if (at === formula.length) break;
            at++;
            if (at === formula.length) throw error('chem_empty_group', at);
        } while (at < formula.length);
        var mass = 0, symbols = Object.keys(counts).sort();
        symbols.forEach(function (symbol) { mass += counts[symbol] * weights[symbol]; });
        if (!Number.isFinite(mass) || mass <= 0) throw error('chem_too_large', at);
        var elements = symbols.map(function (symbol) {
            var percent = counts[symbol] * weights[symbol] / mass * part;
            return { symbol: symbol, count: counts[symbol], atomicWeight: weights[symbol], percent: percent, ppm: percent * 10000 };
        });
        return { formula: formula.replace(/[.*]/g, '·'), molarMass: mass, part: part, elements: elements };
    }
    api.chemistry = { calculate: calculate };
    if (typeof module !== 'undefined' && module.exports) module.exports = api.chemistry;
}(typeof window !== 'undefined' ? window : globalThis));
