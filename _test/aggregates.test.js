'use strict';
const test = require('node:test'), assert = require('node:assert/strict'), fs = require('node:fs'), vm = require('node:vm');
const engine = require('../modules/engine.js');
const fixture = JSON.parse(fs.readFileSync(__dirname + '/aggregates.json', 'utf8'));
const run = (formula, list) => engine.compile(formula, ['factor', 'value'], ['quantities'])({quantities:list, factor:2, value:2});
test('PHP and JavaScript accept the same aggregate expressions', () => {
    for (const [formula, expected] of fixture.cases) {
        let accepted = true;
        try { engine.compile(formula, ['factor','value'], ['quantities']); } catch (e) { accepted = false; }
        assert.equal(accepted, expected, formula);
    }
});
test('Aggregates ignore empty rows and include numeric zero', () => {
    const list = [-2, null, 0, 5, null];
    for (const [fn, expected] of [['sum',3], ['count',3], ['avg',1], ['min',-2], ['max',5]]) assert.equal(run(fn+'(quantities)',list), expected);
    assert.equal(run('min(avg(quantities), 2)', list), 1);
});
test('Empty lists propagate missing results through expressions', () => {
    for (const list of [[], [null, null]]) {
        assert.equal(run('sum(quantities)', list), 0);
        assert.equal(run('count(quantities)', list), 0);
        for (const formula of ['avg(quantities)', 'min(quantities)', 'max(quantities)', '-avg(quantities)', 'avg(quantities)+1', 'abs(avg(quantities))', 'min(2,avg(quantities))']) {
            assert.equal(run(formula, list), null, formula);
        }
    }
});
test('Numeric lists support aggregates in scalar and list outputs', () => {
    const def = {inputs:[{name:'quantities',type:'decimal-list'}],outputs:[
        {name:'mean',type:'decimal',formula:'avg(quantities)',decimals:3},
        {name:'shares',type:'decimal-list',source:'quantities',formula:'value / sum(quantities) * 100',decimals:3}
    ]};
    const results = engine.calculate(def,{quantities:'1\n\n3\n0'});
    assert.equal(results[0].values[0], 4/3);
    assert.deepEqual(results[1].values,[25,null,75,0]);
    assert.deepEqual(engine.calculate(def,{quantities:''}).map(r=>r.values), [[null],[null]]);
    assert.throws(()=>engine.calculate(def,{quantities:'1\nabc'}), e=>e.code==='invalid_number' && e.line===2);
});
test('Recipe text is preserved and the carrier remainder uses unrounded values', () => {
    const def=fixture.definition, field=def.inputs[3];
    let response; const self={postMessage:r=>response=JSON.parse(JSON.stringify(r))};
    vm.runInNewContext(fs.readFileSync(__dirname+'/../modules/regex-worker.js','utf8'),{self});
    self.onmessage({data:{lines:['ITEM-A\t0,500\tkg','ITEM-B\t0,060\tkg','ITEM-C\t0,040\tkg','','Text only'],pattern:field.pattern,flags:field.flags}});
    assert.equal(response.ok,true);
    const raw={batch_quantity:'1',original_dosage:'2',target_dosage:'3'};
    const results=engine.calculate(def,raw,{quantities:response.rows});
    assert.equal(engine.formatResult(results[0],'de-DE'),'ITEM-A\t0,333\tkg\nITEM-B\t0,040\tkg\nITEM-C\t0,027\tkg\n\nText only');
    assert.ok(Math.abs(results[1].values[0]-.4)<1e-12);
    assert.ok(Math.abs(results[2].values[0]-.6)<1e-12);
    assert.equal(results[3].values[0],3);
    const negative=engine.calculate(def,{...raw,target_dosage:'.5'},{quantities:response.rows});
    assert.ok(negative[2].values[0]<0);
    const empty=engine.calculate(def,raw,{quantities:[{text:'Text only'}]});
    assert.equal(empty[3].values[0],0);
    assert.equal(empty[2].values[0],1);
});
test('Large lists, overflow and existing scalar functions', () => {
    assert.equal(run('count(quantities)', Array(10000).fill(1)),10000);
    assert.equal(run('sum(quantities)', Array(10000).fill(1)),10000);
    assert.equal(run('avg(quantities)', [1e308,1e308]),1e308);
    assert.throws(()=>run('sum(quantities)',[1e308,1e308]), /nonfinite/);
    assert.throws(()=>run('1 / sum(quantities)',[]), /division_zero/);
    assert.equal(engine.compile('min(2,3)+max(1,4)',[])({}),6);
});
test('Empty regex inputs pass through asynchronous preparation', async () => {
    class Worker {
        postMessage(data) {
            const self = {postMessage: response => queueMicrotask(() => this.onmessage({data:response}))};
            vm.runInNewContext(fs.readFileSync(__dirname+'/../modules/regex-worker.js','utf8'),{self});
            self.onmessage({data});
        }
        terminate() {}
    }
    const window={DokuCalculator:{version:'test'},Worker,setTimeout,clearTimeout};
    vm.runInNewContext(fs.readFileSync(__dirname+'/../modules/regex.js','utf8'),{window,Worker,DOKU_BASE:'/'});
    for (const text of ['', ' \n\t', 'No quantities']) {
        const prepared = await window.DokuCalculator.regexInput.prepare(fixture.definition,{quantities:text}).promise;
        const result=engine.calculate(fixture.definition,{batch_quantity:'1',original_dosage:'2',target_dosage:'3'},prepared);
        assert.equal(result[3].values[0],0);
        assert.equal(result[2].values[0],1);
    }
});
