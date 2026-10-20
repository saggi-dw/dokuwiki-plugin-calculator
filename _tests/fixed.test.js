'use strict';
const test = require('node:test'), assert = require('node:assert/strict');
const engine = require('../modules/engine.js');
test('Fixed decimal places, rounding and locales for scalar values and numeric lists', () => {
    const result = {output: {decimals: 3, fixed: true}, values: [0.15, 2, null, 1.23456, -0]};
    assert.equal(engine.formatResult(result, 'de-DE'), '0,150\n2,000\n\n1,235\n0,000');
    assert.equal(engine.formatResult(result, 'en-US'), '0.150\n2.000\n\n1.235\n0.000');
    assert.deepEqual(result.values, [0.15, 2, null, 1.23456, -0]);
    result.output.decimals = 0;
    assert.equal(engine.formatResult(result, 'de-DE'), '0\n2\n\n1\n0');
});
test('Default and explicit false retain variable decimal places', () => {
    for (const fixed of [undefined, false]) {
        assert.equal(engine.formatResult({output: {decimals: 3, fixed}, values: [0.15, 2]}, 'de-DE'), '0,15\n2');
    }
});
test('Text lists pad only the replaced numbers', () => {
    const result = {output: {decimals: 3, fixed: true}, values: [0.15, null, null], rows: [
        {text: 'Product\t0,150\tKG\tNo', start: 8, end: 13},
        {text: 'Description without a number'}, {text: ''}
    ]};
    assert.equal(engine.formatResult(result, 'de-DE'), 'Product\t0,150\tKG\tNo\nDescription without a number\n');
    result.values[0] = 0.375;
    assert.equal(engine.formatResult(result, 'de-DE'), 'Product\t0,375\tKG\tNo\nDescription without a number\n');
});
