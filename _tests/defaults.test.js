'use strict';
const test=require('node:test'),assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm');
const engine=require('../modules/engine.js');
const fixture=JSON.parse(fs.readFileSync(__dirname+'/defaults.json','utf8'));
test('Default regex table passes unchanged to the worker and is summed',()=>{
    const def=fixture.definition, field=def.inputs[0];
    let response;const self={postMessage:r=>response=JSON.parse(JSON.stringify(r))};
    vm.runInNewContext(fs.readFileSync(__dirname+'/../modules/regex-worker.js','utf8'),{self});
    self.onmessage({data:{lines:field.default.split('\n'),pattern:field.pattern,flags:field.flags}});
    assert.equal(response.ok,true);
    const result=engine.calculate(def,{quantities:field.default},{quantities:response.rows});
    assert.ok(Math.abs(result[0].values[0]-.6)<1e-12);
    assert.equal(engine.calculate(fixture.decimal,{quantities:fixture.decimal.inputs[0].default})[0].values[0],4);
});
test('The interface preserves list text and localizes scalar numbers only',()=>{
    const created=[];
    function element(tag){
        const node={tag,children:[],appendChild(child){this.children.push(child);},setAttribute(){},addEventListener(){}};
        created.push(node);return node;
    }
    const window={DokuCalculator:{modules:{}}},document={createElement:element};
    vm.runInNewContext(fs.readFileSync(__dirname+'/../modules/user.js','utf8'),{window,document});
    const def={title:'Test',inputs:[
        {name:'text',type:'regex-list',label:'Text',default:'Product 1.2\t0.150 kg\n\n<script>Text</script>'},
        {name:'numbers',type:'decimal-list',label:'Numbers',default:'1.5\n2.5'},
        {name:'number',type:'decimal',label:'Number',default:2.5}
    ],outputs:[]};
    for(const locale of ['de-DE','en-US']){
        created.length=0;window.DokuCalculator.modules.user(element('host'),def,{locale});
        const controls=created.filter(node=>node.tag==='textarea'||node.tag==='input');
        assert.equal(controls[0].value,def.inputs[0].default);
        assert.equal(controls[1].value,'1.5\n2.5');
        assert.equal(controls[2].value,locale==='de-DE'?'2,5':'2.5');
        assert.equal(created.some(node=>node.tag==='script'),false);
    }
});
