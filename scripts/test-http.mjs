import fs from 'node:fs';
const collection=JSON.parse(fs.readFileSync(new URL('../postman/DoAn.postman_collection.json',import.meta.url)));
const base=process.env.DOAN_API_URL||'http://127.0.0.1:8000/api';
const stamp=Date.now(),variables={base_url:base,username:process.env.DOAN_USERNAME||'admin',password:process.env.DOAN_PASSWORD||'Demo@12345',student_code:'PMSV'+stamp,topic_code:'PMDT'+stamp};
const replace=text=>text.replace(/\{\{(\w+)\}\}/g,(_,key)=>variables[key]??'');
let checks=0;
for(const item of collection.item){
 const request=item.request;
 const response=await fetch(replace(request.url),{method:request.method,headers:{...Object.fromEntries(request.header.map(h=>[h.key,h.value])),...(variables.auth_token?{Authorization:'Bearer '+variables.auth_token}:{})},...(request.body?{body:replace(request.body.raw)}:{}),signal:AbortSignal.timeout(15000)});
 const body=await response.json();
 if(body.data?.access_token)variables.auth_token=body.data.access_token;
 const expected=Number(item.event[0].script.exec[0].match(/status\((\d+)\)/)[1]);
 if(response.status!==expected||typeof body.success!=='boolean')throw new Error(item.name+': expected '+expected+', got '+response.status+' '+JSON.stringify(body));
 for(const line of item.event[0].script.exec){
  const capture=line.match(/set\('([^']+)'/);
  if(capture)variables[capture[1]]=String(capture[1]==='auth_token'?body.data.access_token:body.data.id);
 }
 checks++;console.log('PASS '+item.name);
}
const preflight=await fetch(base+'/sinhvien',{method:'OPTIONS',headers:{Origin:'http://127.0.0.1:8080','Access-Control-Request-Method':'POST','Access-Control-Request-Headers':'content-type'}});
if(preflight.headers.get('access-control-allow-origin')!=='http://127.0.0.1:8080')throw new Error('CORS preflight failed');
const login=await fetch(base+'/auth/login',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({username:variables.username,password:variables.password})});
const isolatedToken=(await login.json()).data.access_token;
const isolated=await fetch('http://127.0.0.1:8002/api/detai',{headers:{Authorization:'Bearer '+isolatedToken}});
if(isolated.status!==404)throw new Error('Student service exposes topic routes');
await fetch(base+'/auth/logout',{method:'POST',headers:{Authorization:'Bearer '+isolatedToken}});
console.log('PASS: '+(checks+2)+' HTTP checks');

