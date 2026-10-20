'use strict';
const test=require('node:test'),assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm');
const engine=require('../modules/engine.js');
const def=JSON.parse(fs.readFileSync(__dirname+'/regex.json','utf8'));
function extract(text,field=def.inputs[2]){
 let response;const self={postMessage:r=>response=JSON.parse(JSON.stringify(r))};
 vm.runInNewContext(fs.readFileSync(__dirname+'/../modules/regex-worker.js','utf8'),{self});
 self.onmessage({data:{lines:text.split('\n'),pattern:field.pattern,flags:field.flags}});
 assert.equal(response.ok,true);return response.rows;
}
function run(text,definition=def){return engine.calculate(definition,{original_servings:'2',target_servings:'5',quantities:text},{quantities:extract(text,definition.inputs[2])})[0];}
test('Replacing numbers preserves other text, secondary numbers and empty rows',()=>{
 const text='• 100 g sugar\n2,5 tbsp flour\n\n2 eggs (optional 1 extra)\nSalt to taste\n';
 const result=run(text);
 assert.equal(engine.formatResult(result,'de-DE'),'• 250 g sugar\n6,25 tbsp flour\n\n5 eggs (optional 1 extra)\nSalt to taste\n');
 assert.deepEqual(result.values,[250,6.25,null,5,null,null]);
});
test('Signs, bullets, decimal points and exact capture positions',()=>{
 assert.equal(engine.formatResult(run('-2 kg\n- 2 kg\n.5 L\n2 x 2'),'de-DE'),'-5 kg\n- 5 kg\n1,25 L\n5 x 2');
 const custom=JSON.parse(JSON.stringify(def));custom.inputs[2].pattern='^v=(?<value>\\d+); other=(\\d+)';
 assert.equal(engine.formatResult(run('v=2; other=2',custom),'de-DE'),'v=5; other=2');
});
test('Unmatched rows remain unchanged, including inputs containing only text',()=>{
 assert.equal(engine.formatResult(run('Salt to taste\nPepper'),'de-DE'),'Salt to taste\nPepper');
});
test('Invalid captures and range violations report their row number',()=>{
 const rows=[{text:'ok'},{text:'abc',start:0,end:3,raw:'abc'}];
 assert.throws(()=>engine.calculate(def,{original_servings:'2',target_servings:'5'},{quantities:rows}),e=>e.code==='invalid_number' && e.line===2);
 const custom=JSON.parse(JSON.stringify(def));custom.inputs[2].min=0;
 assert.throws(()=>run('-2 kg',custom),e=>e.code==='range_error' && e.line===1);
 const bad=JSON.parse(JSON.stringify(def));bad.inputs[2].pattern='^(?<value>\\d+)?Salt';
 assert.throws(()=>run('Salt',bad),e=>e.code==='regex_invalid_capture' && e.line===1);
});
