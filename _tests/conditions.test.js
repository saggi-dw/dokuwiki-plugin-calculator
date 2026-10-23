"use strict";
const test=require('node:test'),assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm');
const engine=require('../modules/engine.js'),fixture=require('./conditions.json');
function run(text=fixture.definition.inputs[0].default,def=fixture.definition){
    let response;
    const self={postMessage:r=>response=JSON.parse(JSON.stringify(r))};
    vm.runInNewContext(fs.readFileSync(__dirname+'/../modules/regex-worker.js','utf8'),{self});
    self.onmessage({data:{lines:text.split('\n'),pattern:def.inputs[0].pattern,flags:def.inputs[0].flags}});
    assert.equal(response.ok,true);
    return engine.calculate(def,{}, {lines:response.rows});
}
test('PHP and JavaScript accept the same conditional expressions',()=>{
    for(const [expression,accepted] of fixture.cases){
        if(accepted) assert.doesNotThrow(()=>engine.compile(expression,fixture.variables,['charges']),expression);
        else assert.throws(()=>engine.compile(expression,fixture.variables,['charges']),expression);
    }
    assert.deepEqual(engine.compile('if(exists(row.item), row.item, sum(charges))',fixture.variables,['charges']).references,['row.item','charges']);
});
test('Alternating numeric captures support nested branches, formatted rows and totals',()=>{
    const results=run();
    assert.deepEqual(results.map(r=>r.values),[[12.5,-2,1.5],[12]]);
    assert.equal(engine.formatResult(results[0],'de-DE'),'Item 12.50 → 12,50 EUR\nDiscount 2.00 → -2,00 EUR\nFee 1.50 → 1,50 EUR');
    assert.deepEqual(run('Header\nItem 0\n\nFee 0\nFooter').map(r=>r.values),[[null,0,null,0,null],[0]]);
});
test('Presence includes zero and empty captures but excludes missing captures',()=>{
    const exists=engine.compile('exists(row.item)',['row.item']);
    assert.equal(exists({'row.item':0}),1);
    assert.equal(exists({'row.item':''}),1);
    assert.equal(exists({}),0);
    const def=structuredClone(fixture.definition);
    def.inputs[0].pattern='^Item (?<item>.*)$';
    def.outputs[0].formula='exists(row.item)';
    assert.deepEqual(run('Item invalid\nItem ',def)[0].values,[1,1]);
    def.outputs[0].formula='if(exists(row.item), row.item, 0)';
    assert.throws(()=>run('Item invalid',def),e=>e.code==='invalid_number' && e.field===def.inputs[0].label && e.line===1 && e.detail==='row.item');
});
test('Unselected branches never read captures or execute arithmetic',()=>{
    assert.equal(engine.compile('if(0,1/0,7)',[])({}),7);
    assert.equal(engine.compile('if(-2,8,sqrt(-1))',[])({}),8);
    assert.equal(engine.compile('if(1,if(0,1/0,4),1/0)',[])({}),4);
    const def=structuredClone(fixture.definition);
    def.outputs[0].formula='if(exists(row.item), 7, 1/0)';
    def.inputs[0].max=1;
    assert.deepEqual(run('Item 99',def)[0].values,[7]);
    assert.throws(()=>run('Fee 1',def),e=>e.code==='division_zero' && e.line===1 && e.field==='Charge');
    def.outputs[0].formula='row.item';
    assert.throws(()=>run('Fee 1',def),e=>e.code==='regex_invalid_capture' && e.detail==='row.item');
    assert.throws(()=>run('Item 99',def),e=>e.code==='range_error' && e.detail.startsWith('row.item:'));
});
test('Null conditions propagate without choosing a branch',()=>{
    const fn=engine.compile('if(avg(items),1/0,2/0)',[],['items']);
    assert.equal(fn({items:[]}),null);
    assert.equal(engine.compile('if(0,1/0,3)',[])({}),3);
});
test('Optional captures do not leak between rows or outputs',()=>{
    const def=structuredClone(fixture.definition);
    def.outputs.splice(1,0,{name:'presence',type:'decimal-list',source:'lines',label:'Item present',formula:'exists(row.item)',decimals:0});
    const result=run('Item 2\nDiscount 1\nItem 0\nFee 1',def);
    assert.deepEqual(result.map(r=>r.values),[[2,-1,0,1],[1,0,1,0],[2]]);
    const legacy=JSON.parse(fs.readFileSync(__dirname+'/row-groups.json','utf8')).legacy;
    legacy.outputs[0].formula='if(exists(row.value), value * row.count, 0)';
    assert.deepEqual(engine.calculate(legacy,{}, {[legacy.inputs[0].name]:[{text:'ABC 2 x 3',raw:'2',start:4,end:5,rawGroups:{value:'2',count:'3'}}]})[0].values,[6]);
});

test('Conditional branches retain static output dependency checks',()=>{
    const def=structuredClone(fixture.definition);
    def.outputs[0].formula='if(0, total, 0)';
    assert.throws(()=>run(undefined,def),e=>e.code==='circular_reference');
    def.outputs[0].formula='if(1, 0, total)';
    def.outputs[1].formula='1 / 0';
    assert.throws(()=>run(undefined,def),e=>e.code==='division_zero' && e.field==='Total');
});
