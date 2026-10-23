'use strict';
const test=require('node:test'),assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm');
const engine=require('../modules/engine.js');
const fixture=JSON.parse(fs.readFileSync(__dirname+'/row-groups.json','utf8'));
function extract(def,text) {
    const field=def.inputs[0];let response;
    const self={postMessage:r=>response=JSON.parse(JSON.stringify(r))};
    vm.runInNewContext(fs.readFileSync(__dirname+'/../modules/regex-worker.js','utf8'),{self});
    self.onmessage({data:{lines:text.split('\n'),pattern:field.pattern,flags:field.flags}});
    assert.equal(response.ok,true);return response.rows;
}
function run(def=fixture.definition,text=def.inputs[0].default) {
    return engine.calculate(def,{}, {[def.inputs[0].name]:extract(def,text)});
}
const clone=()=>JSON.parse(JSON.stringify(fixture.definition));
test('Three named numeric groups produce row prices and an aggregate total',()=>{
    const results=run();
    assert.deepEqual(results.map(r=>r.values),[[31.5,20],[51.5],[2]]);
    assert.equal(engine.formatResult(results[0],'de-DE'),'31,50\n20,00');
    assert.equal(engine.formatResult(results[0],'en-US'),'31.50\n20.00');
});
test('Decimal commas, zero, empty rows and unmatched text preserve row alignment',()=>{
    const text='Heading\n2,5 kg x 3 units @ 4,20 EUR/kg\n\n0 kg x 2 units @ 10 EUR/kg\nFooter';
    const results=run(fixture.definition,text);
    assert.deepEqual(results.map(r=>r.values),[[null,31.5,null,0,null],[31.5],[2]]);
    assert.equal(engine.formatResult(results[0],'en-US'),'\n31.50\n\n0.00\n');
    assert.deepEqual(run(fixture.definition,'').map(r=>r.values),[[null],[0],[0]]);
});
test('Group errors report the source field, group name and row',()=>{
    const def=clone();def.inputs[0].pattern='^(?<quantity>\\S+) (?<count>\\S+) (?<price>\\S+)$';
    assert.throws(()=>run(def,'1 2 3\n1 2 invalid'),e=>e.code==='invalid_number' && e.line===2 && e.field===def.inputs[0].label && e.detail==='row.price');
    assert.throws(()=>run(def,'1 2 -3'),e=>e.code==='range_error' && e.line===1 && e.detail.startsWith('row.price:'));
    def.inputs[0].max=5;
    assert.throws(()=>run(def,'1 2 6'),e=>e.code==='range_error' && e.detail.startsWith('row.price:'));
    def.inputs[0].pattern='^(?<quantity>\\d+) (?<count>\\d+)(?: (?<price>\\d+))?$';
    assert.throws(()=>run(def,'1 2'),e=>e.code==='regex_invalid_capture' && e.detail==='row.price');
});
test('Unused text captures remain compatible with legacy value replacement',()=>{
    const def=fixture.legacy;
    assert.equal(engine.formatResult(run(def,'ABC 2 x 3\nUnmatched')[0],'en-US'),'ABC 6 x 3\nUnmatched');
    const custom=JSON.parse(JSON.stringify(def));custom.outputs[0].formula='row.value * row.count';
    assert.equal(engine.formatResult(run(custom,'ABC 2 x 3')[0],'en-US'),'ABC 6 x 3');
    custom.outputs[0].type='decimal-list';
    assert.deepEqual(run(custom,'ABC 2 x 3')[0].values,[6]);
});
test('Named groups coexist with scalar inputs, constants and forward output references',()=>{
    const def=clone();
    def.inputs.push({name:'price',type:'decimal',label:'Price',min:0});
    def.constants=[{name:'global.factor',scope:'global',value:2}];
    def.outputs[0].formula='row.quantity * row.count * row.price + price * global.factor';
    const results=engine.calculate(def,{price:'1'},{items:extract(def,def.inputs[0].default)});
    assert.deepEqual(results.map(r=>r.values),[[33.5,22],[55.5],[2]]);
    def.outputs.reverse();
    const reversed=engine.calculate(def,{price:'1'},{items:extract(def,def.inputs[0].default)});
    assert.deepEqual(reversed.map(r=>r.values),[[2],[55.5],[33.5,22]]);
});
test('PHP and JS agree on scoped row access; arbitrary property access remains invalid',()=>{
    for(const [formula,expected] of fixture.cases) {
        let accepted=true;try{engine.compile(formula,fixture.variables);}catch(e){accepted=false;}
        assert.equal(accepted,expected,formula);
    }
    for(const [index,formula] of [[0,'row.unknown'],[1,'row.price'],[0,'value'],[1,'sum(items)']]) {
        const def=clone();def.outputs[index].formula=formula;
        assert.throws(()=>run(def),e=>['unknown_variable','invalid_expression'].includes(e.code));
    }
    const def=clone();def.outputs[0].type='text-list';
    assert.throws(()=>run(def),e=>e.code==='regex_text_requires_value');
});
test('Group values are refreshed for each row and errors retain output context',()=>{
    const def=clone();def.outputs[0].formula='row.quantity / row.count';
    assert.deepEqual(run(def)[0].values,[2.5/3,1.25/2]);
    assert.throws(()=>run(def,'1 kg x 0 units @ 2 EUR/kg'),e=>e.code==='division_zero' && e.line===1 && e.field===def.outputs[0].label);
});
