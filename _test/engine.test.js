'use strict';
const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const engine = require('../modules/engine.js');
const definition = JSON.parse(fs.readFileSync(process.argv[2] || __dirname + '/definition.json', 'utf8'));
test('Scale a recipe from two to five servings and preserve decimal commas and rows', () => {
    const result = engine.calculate(definition, { original_servings: '2', target_servings: '5', quantities: '1\n2,5\n\n200\n' });
    assert.deepEqual(result[0].values, [2.5, 6.25, null, 500, null]);
    assert.equal(engine.format(result[0].values, 3, 'de-DE'), '2,5\n6,25\n\n500\n');
});
test('Decimal points, signs, limits and zero values', () => {
    assert.equal(engine.number(' -.5 '), -0.5);
    assert.equal(engine.number('+2,5'), 2.5);
    assert.equal(engine.number('0'), 0);
    assert.equal(engine.format([-0, 1.23456], 3, 'de-DE'), '0\n1,235');
    assert.throws(() => engine.number('1.000,5'), /invalid_number/);
    assert.throws(() => engine.number('2e3'), /invalid_number/);
    assert.throws(() => engine.calculate(definition, { original_servings:'0', target_servings:'5', quantities:'1' }), /range_error/);
});
test('Errors retain row numbers and empty lists preserve empty rows', () => {
    try { engine.calculate(definition, { original_servings:'2', target_servings:'5', quantities:'1\n\nabc' }); assert.fail(); }
    catch (e) { assert.equal(e.line, 3); assert.equal(e.code, 'invalid_number'); }
    assert.deepEqual(engine.calculate(definition, { original_servings:'2', target_servings:'5', quantities:'\n ' })[0].values, [null, null]);
});
test('Expression precedence, powers, signs and functions', () => {
    const run = s => engine.compile(s, ['value'])({value:3});
    assert.equal(run('1+2*3'), 7);
    assert.equal(run('(1+2)*3'), 9);
    assert.equal(run('-2^2'), -4);
    assert.equal(run('2^3^2'), 512);
    assert.equal(run('2^-2'), .25);
    assert.equal(run('max(2, abs(-value)) + sqrt(16)'), 7);
    assert.equal(run('pow(2, 3) % 3'), 2);
    assert.equal(run('min(3,2,1)+round(2.5)+floor(1.9)+ceil(1.1)'), 7);
});
test('Invalid expressions and executable code are rejected', () => {
    for (const code of ['alert(1)', 'value.constructor', 'window', '1;2', 'Function(1)', 'constructor(1)', '1 2', '1+', 'sqrt()', 'min(1)', 'value[0]']) {
        assert.throws(() => engine.compile(code, ['value']), code);
    }
    assert.throws(() => engine.compile('1/0', [])({}), /division_zero/);
    assert.throws(() => engine.compile('sqrt(-1)', [])({}), /nonfinite/);
    assert.throws(() => engine.compile('('.repeat(40)+'1'+')'.repeat(40), []), /invalid_expression/);
    assert.throws(() => engine.compile('2^9999', [])({}), /nonfinite/);
});
test('Raw values remain separate from display formatting', () => {
    const raw = 1/3;
    assert.equal(engine.format([raw, 2], 3, 'de-DE'), '0,333\n2');
    assert.equal(raw, 1/3);
    assert.equal(engine.format([2.5], 3, 'en-US'), '2.5');
});
const scalar = JSON.parse(fs.readFileSync(__dirname + '/scalar.json', 'utf8'));
test('Scalar dilution inputs, ranges and output formatting', () => {
    const run = (a,b) => engine.calculate(scalar, {initial:a,addition:b})[0].values;
    assert.deepEqual(run('40','2'), [.8]);
    assert.equal(engine.format(run('40','2'),3,'de-DE'), '0,8');
    assert.deepEqual(run('0','100'), [0]);
    assert.deepEqual(run('100','100'), [100]);
    assert.throws(() => run('101','2'), /range_error/);
    assert.throws(() => run('','2'), /invalid_number/);
    const inferred = JSON.parse(JSON.stringify(scalar));delete inferred.outputs[0].type;
    assert.deepEqual(engine.calculate(inferred,{initial:'40',addition:'2'})[0].values,[.8]);
});
test('Scalar and list outputs can be combined; value is limited to list expressions', () => {
    const mixed = JSON.parse(JSON.stringify(definition));
    mixed.outputs.push({type:'decimal',name:'total',label:'Parameter',formula:'original_servings+target_servings',decimals:3});
    const result = engine.calculate(mixed,{original_servings:'2',target_servings:'5',quantities:'1\n2'});
    assert.deepEqual(result.map(r=>r.values),[[2.5,5],[7]]);
    mixed.outputs[1].formula='value';
    assert.throws(()=>engine.calculate(mixed,{original_servings:'2',target_servings:'5',quantities:'1'}),/unknown_variable/);
});
