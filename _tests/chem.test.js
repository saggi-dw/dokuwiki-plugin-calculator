'use strict';
const test=require('node:test'),assert=require('node:assert/strict');
require('../modules/chem-weights.js');
const chemistry=require('../modules/chem-engine.js');
function close(a,b,t=1e-8){assert.ok(Math.abs(a-b)<t,`${a} != ${b}`);}
function check(formula,counts,mass){const r=chemistry.calculate(formula);assert.deepEqual(Object.fromEntries(r.elements.map(e=>[e.symbol,e.count])),counts);close(r.molarMass,mass);close(r.elements.reduce((n,e)=>n+e.percent,0),100);close(r.elements.reduce((n,e)=>n+e.ppm,0),1e6,1e-6);return r;}
test('Simple formulas, repeated elements and subscript indices',()=>{
 check('H2O',{H:2,O:1},18.015);check('H₂O',{H:2,O:1},18.015);
 check('TiO2',{O:2,Ti:1},79.865);check('Fe2O3',{Fe:2,O:3},159.687);
 check('CH3COOH',{C:2,H:4,O:2},60.052);check('Cu',{Cu:1},63.546);
});
test('Brackets, hydrates, separators and coefficients',()=>{
 check('Ca(OH)2',{Ca:1,H:2,O:2},74.092);
 check('Al2(SO4)3',{Al:2,O:12,S:3},342.132);
 check('K4[Fe(CN)6]',{C:6,Fe:1,K:4,N:6},368.345);
 check('Mg{OH}2',{H:2,Mg:1,O:2},58.319);
 for(const sep of ['·','*','.'])check('CuSO4'+sep+'5H2O',{Cu:1,H:10,O:9,S:1},249.677);
 check('CH5N3.ClH',{C:1,Cl:1,H:6,N:3},95.53);
 check('2H2O',{H:4,O:2},36.03);
});
test('The requested proportion scales percentages and ppm but preserves molar mass',()=>{
 const a=chemistry.calculate('TiO2'),b=chemistry.calculate('TiO2',40),z=chemistry.calculate('TiO2',0);
 close(a.molarMass,b.molarMass);close(b.elements.reduce((n,e)=>n+e.percent,0),40);
 b.elements.forEach((e,i)=>close(e.percent,a.elements[i].percent*.4));
 z.elements.forEach(e=>{assert.equal(e.percent,0);assert.equal(e.ppm,0);});
 assert.throws(()=>chemistry.calculate('TiO2',101),/chem_part_error/);
 assert.throws(()=>chemistry.calculate('TiO2',NaN),/chem_part_error/);
});
test('Invalid and incomplete formulas are rejected',()=>{
 for(const s of ['', 'tio2','Xx2','H0','0H2O','H2O)','(H2O','[H2O)','()','CuSO4·','·H2O','H2O··H2O','Fe3+','Ti0.5O2','H2 O','Tc','C20H24N2O2.1/2H2O4S','(H2O·H2O)','NaCl2,5','('.repeat(40)+'H'+')'.repeat(40),'H9007199254740992','(H9007199254740991)2'])assert.throws(()=>chemistry.calculate(s),s);
});
test('Unknown elements report their position and notation is normalized',()=>{
 try{chemistry.calculate('H2Xx');assert.fail();}catch(e){assert.equal(e.code,'chem_unknown_element');assert.equal(e.position,3);assert.equal(e.detail,'Xx');}
 assert.equal(chemistry.calculate('CuSO₄*5H₂O').formula,'CuSO4·5H2O');
});
