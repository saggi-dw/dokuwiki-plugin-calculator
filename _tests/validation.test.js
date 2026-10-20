'use strict';
const assert = require('node:assert/strict'), fs = require('node:fs');
const engine = require('../modules/engine.js');
const cases = JSON.parse(fs.readFileSync(__dirname + '/expressions.json','utf8'));
for (const [formula, variables, expected] of cases) {
    let accepted=true;
    try { engine.compile(formula, variables); } catch(e) { accepted=false; }
    assert.equal(accepted, expected, formula);
}
console.log(cases.length+' shared expression cases agree between PHP and JavaScript.');
