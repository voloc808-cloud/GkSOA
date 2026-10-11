import {api} from './api.js';
import {authenticate,can,user} from './auth.js';
import {renderUsers} from './users.js';
import {openGrades,canGrade} from './diem.js';
import sinhvien from './sinhvien.js';
import detai from './detai.js';
import dangky from './dangky.js';
const configs={sinhvien,detai,dangky}, $=s=>document.querySelector(s);
const esc=value=>String(value??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
let page='dashboard',rows=[],students=[],topics=[],version=0;
function notice(message){$('#notice').textContent=message;$('#notice').hidden=!message;}
function display(key,value,cfg){
 if(key.startsWith('diem_'))return value==null?'Chưa chấm':Number(value).toFixed(2);
 if(key==='ket_qua')return esc({chua_cham:'Chưa chấm',dat:'Đạt',khong_dat:'Không đạt'}[value]||value);
 if(key==='trang_thai')return '<span class="badge">'+esc(cfg.statuses[value]||value)+'</span>';
 if(key==='sinh_vien_id'){const row=students.find(x=>x.id===value);return esc(row?row.ma_sinh_vien+' · '+row.ho_ten:value);}
 if(key==='de_tai_id'){const row=topics.find(x=>x.id===value);return esc(row?row.ma_de_tai+' · '+row.ten_de_tai:value);}
 return esc(value);
}
async function navigate(){
 const current=++version;page=location.hash.slice(1)||'dashboard';if(!configs[page]&&page!=='dashboard'&&!(page==='users'&&user?.role==='admin'))page='dashboard';
 if(page==='users'){
  notice('');$('#title').textContent='Tài khoản';document.querySelectorAll('nav a').forEach(a=>a.classList.toggle('active',a.hash==='#users'));
  try{await renderUsers($('#content'),()=>current===version);}catch(e){notice(e.message);}return;
 }
 notice('');$('#title').textContent=configs[page]?.title||'Tổng quan';
 document.querySelectorAll('nav a').forEach(a=>a.classList.toggle('active',a.hash==='#'+page));
 $('#content').innerHTML='<p class="empty">Đang tải dữ liệu…</p>';
 try{
  if(page==='dashboard'){
   const data=await api('/dashboard');if(current!==version)return;
   $('#content').innerHTML='<div class="cards">'+[['sinhvien','Sinh viên'],['detai','Đề tài'],['dangky','Đăng ký'],['con_trong','Đề tài còn chỗ']].map(([k,label])=>'<div class="card"><p>'+label+'</p><strong>'+data[k]+'</strong></div>').join('')+'</div><div class="panel"><h2>Quản lý đồ án tốt nghiệp</h2><p class="intro">Theo dõi sinh viên, quản lý đề tài và duyệt đăng ký tại một nơi. Thêm sinh viên và đề tài, sau đó tạo đăng ký.</p><a href="#detai">Khám phá danh sách đề tài →</a></div>';
  }else{
   const cfg=configs[page];
   if(page==='dangky')[students,topics]=await Promise.all([api('/sinhvien'),api('/detai')]);
   if(current!==version)return;
   $('#content').innerHTML='<div class="panel"><div class="toolbar">'+(page!=='dangky'?'<input id="search" placeholder="Tìm theo mã hoặc tên" aria-label="Tìm kiếm">':'<select id="student-filter" aria-label="Lọc sinh viên"><option value="">Tất cả sinh viên</option>'+students.map(s=>'<option value="'+s.id+'">'+esc(s.ma_sinh_vien+' · '+s.ho_ten)+'</option>').join('')+'</select><select id="topic-filter" aria-label="Lọc đề tài"><option value="">Tất cả đề tài</option>'+topics.map(t=>'<option value="'+t.id+'">'+esc(t.ten_de_tai)+'</option>').join('')+'</select>')+(cfg.statuses?'<select id="status-filter" aria-label="Lọc trạng thái"><option value="">Tất cả trạng thái</option>'+Object.entries(cfg.statuses).map(([k,v])=>'<option value="'+k+'">'+v+'</option>').join('')+'</select>':'')+'<button id="filter" class="secondary">Tìm kiếm</button><button id="add">+ Thêm '+cfg.singular+'</button></div><div id="table"></div></div>';
   $('#add').hidden=!can(page,'add');$('#add').onclick=()=>openEditor().catch(e=>notice(e.message));$('#filter').onclick=loadRows;
   $('#search')?.addEventListener('keydown',e=>{if(e.key==='Enter')loadRows();});
   await loadRows();
  }
 }catch(e){if(current===version){notice(e.message);$('#content').innerHTML='<p class="empty">Không tải được dữ liệu. Kiểm tra gateway và các dịch vụ đang chạy.</p>';}}
}
async function loadRows(){
 const current=version,resource=page,params=new URLSearchParams();
 for(const [selector,key] of [['#search','q'],['#status-filter','trang_thai'],['#student-filter','sinh_vien_id'],['#topic-filter','de_tai_id']]){const value=$(selector)?.value;if(value)params.set(key,value);}
 try{
  const result=await api('/'+resource+'?'+params);if(current!==version)return;rows=result;
  const cfg=configs[resource];
  $('#table').innerHTML=rows.length?'<table><thead><tr>'+cfg.columns.map(k=>'<th>'+cfg.fields[k]+'</th>').join('')+'<th>Thao tác</th></tr></thead><tbody>'+rows.map(row=>'<tr>'+cfg.columns.map(k=>'<td>'+display(k,row[k],cfg)+'</td>').join('')+'<td><button class="secondary" data-action="detail" data-id="'+row.id+'">Chi tiết</button><button class="secondary" data-action="edit" data-id="'+row.id+'">Sửa</button>'+(resource==='dangky'&&['cho_duyet','da_duyet'].includes(row.trang_thai)?'<button class="secondary" data-action="cancel" data-id="'+row.id+'">Huỷ</button>':'')+'<button class="danger" data-action="delete" data-id="'+row.id+'">Xoá</button></td></tr>').join('')+'</tbody></table>':'<p class="empty">Chưa có dữ liệu phù hợp.</p>';
  $('#table').querySelectorAll('button[data-action]').forEach(button=>{button.hidden=!can(resource,button.dataset.action,rows.find(r=>Number(r.id)===Number(button.dataset.id)));});
  if(resource==='detai'&&user.role==='sinhvien')$('#table').querySelectorAll('tbody tr').forEach((tr,i)=>{
   const row=rows[i];if(!can(resource,'register',row))return;
   const button=document.createElement('button');button.textContent='Đăng ký';button.dataset.action='register';button.dataset.id=row.id;tr.lastElementChild.append(button);
  });
  if(resource==='dangky')$('#table').querySelectorAll('tbody tr').forEach((tr,i)=>{
   const row=rows[i];if(!canGrade(row))return;
   for(const [action,label] of [['grade','Chấm điểm'],...(row.diem_tong_ket==null?[]:[['clear-grade','Xóa điểm']])]){
    const button=document.createElement('button');button.className='secondary';button.textContent=label;button.dataset.action=action;button.dataset.id=row.id;tr.lastElementChild.append(button);
   }
  });
  $('#table').onclick=async e=>{
   const button=e.target.closest('button');if(!button)return;
   const id=Number(button.dataset.id),action=button.dataset.action;
   try{
    if(action==='grade')return await openGrades(id,loadRows);
    if(action==='clear-grade'){
     if(!confirm('Xóa cả ba điểm của đăng ký này?'))return;
     button.disabled=true;await api('/dangky/'+id+'/diem','DELETE');notice('');await loadRows();return;
    }
    if(action==='register')return await openEditor(undefined,rows.find(r=>r.id===id));
    if(action==='edit')return await openEditor(rows.find(r=>r.id===id));
    if(action==='detail')return await showDetail(id);
    if(!confirm(action==='cancel'?'Huỷ đăng ký này?':'Xoá bản ghi này?'))return;
    button.disabled=true;
    await api('/'+resource+'/'+id,action==='cancel'?'PUT':'DELETE',action==='cancel'?{trang_thai:'da_huy'}:undefined);
    notice('');await loadRows();
   }catch(err){notice(err.message);button.disabled=false;}
  };
 }catch(e){if(current===version)notice(e.message);}
}
async function showDetail(id){
 $('#fields').oninput=null;
 const resource=page,cfg=configs[resource];
 const row=await api('/'+resource+'/'+id);
 $('#modal-title').textContent='Chi tiết '+cfg.singular;
 $('#fields').innerHTML='<dl>'+Object.keys(cfg.fields).map(k=>'<dt>'+cfg.fields[k]+'</dt><dd>'+display(k,row[k],cfg)+'</dd>').join('')+'</dl>';
 if(resource!=='dangky'){
  const related=await api('/dangky?'+(resource==='sinhvien'?'sinh_vien_id':'de_tai_id')+'='+id);
  const [ss,tt]=await Promise.all([api('/sinhvien'),api('/detai')]);
  $('#fields').innerHTML+='<h3>Đăng ký liên quan ('+related.length+')</h3><ul>'+related.map(r=>{const linked=resource==='sinhvien'?tt.find(t=>t.id===r.de_tai_id):ss.find(s=>s.id===r.sinh_vien_id);return '<li>'+esc(linked?(linked.ten_de_tai||linked.ho_ten):'Không tìm thấy')+' · '+esc(dangky.statuses[r.trang_thai])+'</li>';}).join('')+'</ul>';
 }
 $('#editor').onsubmit=e=>e.preventDefault();$('#form-error').textContent='';$('#save').hidden=true;$('#modal').showModal();
}
async function openEditor(row,selectedTopic){
 $('#fields').oninput=null;
 const resource=selectedTopic?'dangky':page,cfg=configs[resource];
 const lecturers=resource==='detai'&&user.role==='admin'?(await api('/users')).filter(a=>a.role==='giangvien'):[];
 if(resource==='dangky'&&!row)[students,topics]=await Promise.all([api('/sinhvien'),api('/detai')]);
 $('#modal-title').textContent=(row?'Cập nhật ':'Thêm ')+cfg.singular;
 let keys=Object.keys(cfg.fields);
 if(resource==='detai'&&user.role==='admin')keys.push('giang_vien_id');
 if(resource==='dangky')keys=row?['de_tai_id','trang_thai']:['sinh_vien_id','de_tai_id'];
 if(resource==='detai'&&user.role==='giangvien')keys=keys.filter(k=>k!=='giang_vien');
 $('#fields').innerHTML=keys.map(k=>{
  let control;
  if(k==='giang_vien_id')control='<select name="giang_vien_id"><option value="">Chưa liên kết tài khoản</option>'+lecturers.map(a=>'<option value="'+a.id+'" '+(Number(row?.giang_vien_id)===a.id?'selected':'')+'>'+esc(a.name+' · '+a.username)+'</option>').join('')+'</select>';
  else if(k==='trang_thai')control='<select name="'+k+'">'+Object.entries(cfg.statuses).filter(([v])=>v!=='da_du'||row).map(([v,label])=>'<option value="'+v+'" '+(row?.[k]===v?'selected':'')+'>'+label+'</option>').join('')+'</select>';
  else if(resource==='dangky'){const choices=k==='sinh_vien_id'?students:topics.filter(t=>t.trang_thai==='con_trong'||t.id===row?.de_tai_id);control='<select name="'+k+'" required><option value="">Chọn '+cfg.fields[k].toLowerCase()+'</option>'+choices.map(x=>'<option value="'+x.id+'" '+(x.id===row?.[k]?'selected':'')+'>'+esc(x.ho_ten?x.ma_sinh_vien+' · '+x.ho_ten:x.ten_de_tai)+'</option>').join('')+'</select>';}
  else if(k==='mo_ta')control='<textarea name="'+k+'">'+esc(row?.[k]||'')+'</textarea>';
  else control='<input name="'+k+'" type="'+(k==='email'?'email':k==='so_luong_sinh_vien'?'number':'text')+'" '+(k==='so_luong_sinh_vien'?'min="1" max="1000"':'maxlength="'+(k.startsWith('ma_')?30:k==='lop'?50:k==='ten_de_tai'?255:150)+'"')+' required value="'+esc(row?.[k]??(k==='so_luong_sinh_vien'?2:''))+'">';
  return '<label>'+(k==='giang_vien_id'?'Tài khoản giảng viên phụ trách':cfg.fields[k])+control+'</label>';
 }).join('');
 if(resource==='dangky'&&user.role==='sinhvien')$('#fields [name=sinh_vien_id]').value=user.sinh_vien_id;
 if(selectedTopic)$('#fields [name=de_tai_id]').value=selectedTopic.id;
 if(resource==='dangky'&&row&&user.role==='giangvien')$('#fields select').querySelectorAll('option').forEach(o=>{if(!['da_duyet','tu_choi'].includes(o.value))o.remove();});
 $('#form-error').textContent='';$('#save').hidden=false;$('#save').disabled=false;$('#modal').showModal();
 $('#editor').onsubmit=async e=>{
  e.preventDefault();const data=Object.fromEntries(new FormData(e.target));
  if('giang_vien_id' in data){data.giang_vien_id=data.giang_vien_id?Number(data.giang_vien_id):null;const lecturer=lecturers.find(a=>a.id===data.giang_vien_id);if(lecturer)data.giang_vien=lecturer.name;}
  for(const key of ['sinh_vien_id','de_tai_id','so_luong_sinh_vien'])if(key in data)data[key]=Number(data[key]);
  $('#save').disabled=true;
  try{await api('/'+resource+(row?'/'+row.id:''),row?'PUT':'POST',data);$('#modal').close();notice('');await loadRows();}
  catch(err){$('#form-error').textContent=err.message;}finally{$('#save').disabled=false;}
 };
}
$('#close').onclick=()=>$('#modal').close();
$('#refresh').onclick=navigate;
window.addEventListener('hashchange',()=>{$('#modal').close();navigate();});
await authenticate();
navigate();

