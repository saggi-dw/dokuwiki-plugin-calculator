'use strict';
const test=require('node:test'), assert=require('node:assert/strict'), fs=require('node:fs');
const engine=require('../modules/engine.js');
const {definition:def,textDefinition,example}=JSON.parse(fs.readFileSync(__dirname+'/output-references.json','utf8'));
const clone=()=>JSON.parse(JSON.stringify(def));
test('Forward references and multiple dependencies preserve display order',()=>{
    const result=engine.calculate(def,{quantities:'1\n1\n1'});
    assert.deepEqual(result.map(r=>r.output.name),['gross_total','tax','net_total','net_amounts']);
    assert.equal(result[0].values[0],1.19);
    assert.equal(result[1].values[0],.19);
    assert.equal(result[2].values[0],1);
    assert.equal(engine.formatResult(result[3],'de-DE'),'0,33\n0,33\n0,33');
    assert.equal(result[3].values[0],1/3);
    const vat=engine.calculate(example,{quantities:'2\n3',price:'10'});
    assert.equal(vat[3].values[0],59.5);
});
test('All aggregate functions accept output lists',()=>{
    for(const [fn,expected] of [['sum',3],['count',2],['avg',1.5],['min',1],['max',2]]){
        const custom=clone();custom.outputs[0].formula=fn+'(net_amounts)';
        assert.equal(engine.calculate(custom,{quantities:'3\n\n6'})[0].values[0],expected);
    }
});
test('Calculated scalars and list aggregates work in row expressions',()=>{
    const custom=clone();custom.outputs.push({name:'shares',type:'decimal-list',source:'quantities',label:'Shares',formula:'value / net_total + sum(net_amounts)',decimals:3});
    const result=engine.calculate(custom,{quantities:'3\n\n6'});
    assert.deepEqual(result[4].values,[4,null,5]);
});
test('Direct, indirect and mixed circular references are rejected in JavaScript',()=>{
    for(const [index,formula] of [[0,'gross_total'],[1,'gross_total'],[3,'value + net_total']]){
        const custom=clone();custom.outputs[index].formula=formula;
        assert.throws(()=>engine.calculate(custom,{quantities:'1'}), e=>e.code==='circular_reference' && e.detail.includes(' -> ') && !!e.field);
    }
});
test('Missing results remain empty when referenced by other outputs',()=>{
    const custom=clone();custom.outputs[2].formula='avg(net_amounts)';
    const result=engine.calculate(custom,{quantities:''});
    assert.equal(result[0].values[0],null);
    assert.equal(result[1].values[0],0);
    assert.equal(result[2].values[0],null);
    assert.equal(engine.formatResult(result[0],'de-DE'),'');
});
test('Text list aggregates use numeric results instead of formatted text',()=>{
    const result=engine.calculate(textDefinition,{}, {quantities:[
        {text:'Product A 1 kg',raw:'1',start:10,end:11},
        {text:'Product B 2 kg',raw:'2',start:10,end:11},
        {text:'No quantity'}
    ]});
    assert.equal(result[2].values[0],1);
    assert.equal(result[0].values[0],1.19);
    assert.equal(engine.formatResult(result[3],'de-DE'),'Product A 0,33 kg\nProduct B 0,67 kg\nNo quantity');
});
test('Errors identify the originating output field and input row',()=>{
    const custom=clone();custom.outputs[3].formula='1 / value';
    assert.throws(()=>engine.calculate(custom,{quantities:'1\n0'}), e=>e.code==='division_zero' && e.field==='Net amounts' && e.line===2);
    custom.outputs[0].formula='net_amounts';
    assert.throws(()=>engine.calculate(custom,{quantities:'1'}),e=>e.code==='unknown_variable');
});
