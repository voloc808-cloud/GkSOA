import {api} from './api.js';
import {user} from './auth.js';
const roles={admin:'Admin',giangvien:'Giảng viên',sinhvien:'Sinh viên'};
const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
export async function renderUsers(content,isCurrent){
 if(user.role!=='admin')throw new Error('Chỉ admin được quản lý tài khoản.');
 const [accounts,students]=await Promise.all([api('/users'),api('/sinhvien')]);
 if(!isCurrent())return;
 content.innerHTML='<div class="panel"><div class="toolbar"><p>Admin tạo tài khoản và cấp vai trò. Mật khẩu được lưu dưới dạng băm và không thể xem lại.</p><button id="create-account">+ Cấp tài khoản</button></div><table><thead><tr><th>Tên đăng nhập</th><th>Họ tên</th><th>Vai trò</th><th>Hồ sơ sinh viên</th><th>Thao tác</th></tr></thead><tbody>'+accounts.map(a=>'<tr><td>'+esc(a.username)+'</td><td>'+esc(a.name)+'</td><td>'+roles[a.role]+'</td><td>'+esc(students.find(s=>s.id===a.sinh_vien_id)?.ma_sinh_vien||'—')+'</td><td><button data-edit="'+a.id+'">Sửa quyền / mật khẩu</button> <button class="danger" data-delete="'+a.id+'" '+(a.id===user.id?'disabled':'')+'>Xóa</button></td></tr>').join('')+'</tbody></table><p id="account-error" role="alert"></p></div>';
 const reload=()=>renderUsers(content,isCurrent);
 function edit(account){
  const dialog=document.createElement('dialog');
  dialog.innerHTML='<form><h2>'+(account?'Cập nhật tài khoản':'Cấp tài khoản')+'</h2><label>Tên đăng nhập<input name="username" maxlength="50" required value="'+esc(account?.username)+'"></label><label>Họ tên<input name="name" maxlength="150" required value="'+esc(account?.name)+'"></label><label>Mật khẩu'+(account?' (để trống nếu giữ nguyên)':'')+'<input name="password" type="password" autocomplete="new-password" minlength="8" maxlength="128" '+(account?'':'required')+'></label><label>Vai trò<select name="role">'+Object.entries(roles).map(([k,v])=>'<option value="'+k+'" '+((account?.role||'giangvien')===k?'selected':'')+'>'+v+'</option>').join('')+'</select></label><label id="student-link">Hồ sơ sinh viên<select name="sinh_vien_id"><option value="">Chọn sinh viên</option>'+students.filter(s=>!accounts.some(a=>a.sinh_vien_id===s.id&&a.id!==account?.id)).map(s=>'<option value="'+s.id+'" '+(account?.sinh_vien_id===s.id?'selected':'')+'>'+esc(s.ma_sinh_vien+' · '+s.ho_ten)+'</option>').join('')+'</select></label><p role="alert"></p><button type="submit">Lưu</button> <button type="button" id="account-close" class="secondary">Đóng</button></form>';
  document.body.append(dialog);dialog.showModal();dialog.addEventListener('close',()=>dialog.remove());
  const role=dialog.querySelector('[name=role]'),student=dialog.querySelector('[name=sinh_vien_id]');
  const sync=()=>{const active=role.value==='sinhvien';dialog.querySelector('#student-link').hidden=!active;student.required=active;student.disabled=!active;};sync();role.onchange=sync;
  dialog.querySelector('#account-close').onclick=()=>dialog.close();
  dialog.querySelector('form').onsubmit=async e=>{
   e.preventDefault();const data=Object.fromEntries(new FormData(e.target));data.sinh_vien_id=data.role==='sinhvien'?Number(data.sinh_vien_id):null;
   if(!data.password)delete data.password;const save=dialog.querySelector('[type=submit]');save.disabled=true;
   try{await api('/users'+(account?'/'+account.id:''),account?'PUT':'POST',data);dialog.close();
    if(account?.id===user.id){sessionStorage.removeItem('doan_token');location.reload();return;}await reload();
   }catch(error){dialog.querySelector('[role=alert]').textContent=error.message;}finally{save.disabled=false;}
  };
 }
 content.querySelector('#create-account').onclick=()=>edit();
 content.querySelector('tbody').onclick=async e=>{
  const button=e.target.closest('button');if(!button)return;
  const account=accounts.find(a=>a.id===Number(button.dataset.edit||button.dataset.delete));
  if(button.dataset.edit){edit(account);return;}
  if(!confirm('Xóa tài khoản '+account.username+'?'))return;button.disabled=true;
  try{await api('/users/'+account.id,'DELETE');await reload();}catch(error){content.querySelector('#account-error').textContent=error.message;button.disabled=false;}
 };
}
