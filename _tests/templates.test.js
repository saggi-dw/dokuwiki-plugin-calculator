"use strict";
const test=require('node:test'),assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm');
const engine=require('../modules/engine.js');
const fixture=require('./templates.json');
function run(text=fixture.inputs[0].default, definition=fixture) {
    let response;
    const self={postMessage:r=>response=JSON.parse(JSON.stringify(r))};
    vm.runInNewContext(fs.readFileSync(__dirname+'/../modules/regex-worker.js','utf8'),{self});
    self.onmessage({data:{lines:text.split('\n'),pattern:definition.inputs[0].pattern,flags:definition.inputs[0].flags}});
    assert.equal(response.ok,true);
    return engine.calculate(definition,{}, {items:response.rows});
}
test('Original lines are preserved with localized prices and raw aggregate values',()=>{
    const results=run();
    assert.equal(engine.formatResult(results[0],'de-DE'),'2.5 kg x 3 units @ 4.20 EUR/kg → Price per row: 31,50 EUR\n1.25 kg x 2 units @ 8.00 EUR/kg → Price per row: 20,00 EUR');
    assert.deepEqual(results.map(r=>r.values),[[31.5,20],[51.5],[2]]);
});
test('Unmatched and blank rows stay unchanged; templates support either order',()=>{
    const def=structuredClone(fixture);def.outputs[0].template='{value} {unit} ← {line}';
    const results=run('Header\n1.25 kg x 2 units @ 8.00 EUR/kg\n\nFooter',def);
    assert.equal(engine.formatResult(results[0],'en-US'),'Header\n20.00 EUR ← 1.25 kg x 2 units @ 8.00 EUR/kg\n\nFooter');
    assert.deepEqual(results[1].values,[20]);
});
test('Numeric input lines preserve spacing and rounding does not change aggregation',()=>{
    const def={inputs:[{name:'numbers',type:'decimal-list',label:'Numbers'}],outputs:[
        {name:'thirds',type:'decimal-list',source:'numbers',label:'Third',formula:'value / 3',decimals:2,fixed:true,template:'{line} = {value}{unit}'},
        {name:'total',type:'decimal',label:'Total',formula:'sum(thirds)',decimals:2,fixed:true,template:'{label}: {value}{unit}'}]};
    const results=engine.calculate(def,{numbers:' 1 \r\n\t1\r\n\r\n1'});
    assert.equal(engine.formatResult(results[0],'en-US'),' 1  = 0.33\n\t1 = 0.33\n\n1 = 0.33');
    assert.deepEqual(results[1].values,[1]);
    assert.equal(engine.formatResult(results[1],'en-US'),'Total: 1.00');
});
test('Templates override legacy replacement without changing its default',()=>{
    const legacy=structuredClone(require('./row-groups.json').legacy);
    const row={text:'ABC 2 x 3',raw:'2',start:4,end:5,rawGroups:{prefix:'ABC',value:'2',count:'3'}};
    let result=engine.calculate(legacy,{}, {[legacy.inputs[0].name]:[row]})[0];
    assert.equal(engine.formatResult(result,'en-US'),'ABC 6 x 3');
    legacy.outputs[0].template='{line}: {value}';
    result=engine.calculate(legacy,{}, {[legacy.inputs[0].name]:[row]})[0];
    assert.equal(engine.formatResult(result,'en-US'),'ABC 2 x 3: 6');
});
test('Inserted text is literal and placeholders are replaced only once',()=>{
    const result={output:{template:'{line} {label} {value} {unit}',label:'{value} $&',unit:'<b>EUR</b>',decimals:2},values:[-0],rows:[{text:'{label} $1 <img>'}]};
    assert.equal(engine.formatResult(result,'en-US'),'{label} $1 <img> {value} $& 0 <b>EUR</b>');
});
test('Malformed templates fail at runtime as well',()=>{
    for(const template of ['', '{unknown}', '{value', 'x}', 'x\ny', 'x'.repeat(2001), 'ü'.repeat(1001), '{line}']) {
        assert.throws(()=>engine.formatResult({output:{template,decimals:2},values:[1]},'en-US'),e=>e.code==='invalid_template');
    }
});
