import {api} from './api.js';
export let user=null;
export function can(resource,action,row){
 if(user?.role==='admin')return true;
 if(action==='detail')return true;
 if(resource==='detai'&&action==='register')return user?.role==='sinhvien'&&row?.trang_thai==='con_trong';
 if(resource==='users')return false;
 if(resource==='detai')return user?.role==='giangvien'&&action==='add';
 if(resource==='dangky')return user?.role==='sinhvien'&&action==='add';
 return false;
}
export async function authenticate(){
 if(sessionStorage.getItem('doan_token')){try{user=await api('/auth/me');}catch{sessionStorage.removeItem('doan_token');}}
 if(!user)await new Promise(resolve=>{
  const dialog=document.createElement('dialog');
  dialog.innerHTML='<form><h2>Đăng nhập</h2><label>Tên đăng nhập<input name="username" autocomplete="username" required></label><label>Mật khẩu<input name="password" type="password" autocomplete="current-password" required></label><p role="alert"></p><button>Đăng nhập</button></form>';
  document.body.append(dialog);dialog.addEventListener('cancel',e=>e.preventDefault());dialog.showModal();
  dialog.querySelector('form').onsubmit=async e=>{e.preventDefault();const button=dialog.querySelector('button');button.disabled=true;
   try{const result=await api('/auth/login','POST',Object.fromEntries(new FormData(e.target)));sessionStorage.setItem('doan_token',result.access_token);user=result.user;dialog.close();dialog.remove();resolve();}
   catch(error){dialog.querySelector('p').textContent=error.message;}finally{button.disabled=false;}
  };
 });
 if(user.role==='admin'){const link=document.createElement('a');link.href='#users';link.textContent='Tài khoản';document.querySelector('nav').append(link);}
 if(user.role==='sinhvien'){
  document.querySelector('a[href="#sinhvien"]').hidden=true;
  document.querySelector('a[href="#dangky"]').textContent='Đăng ký của tôi';
 }
 if(user.role==='giangvien'){
  document.querySelector('a[href="#sinhvien"]').textContent='Sinh viên đăng ký';
  document.querySelector('a[href="#detai"]').textContent='Đề tài của tôi';
 }
 const box=document.createElement('div');box.textContent=user.name+' · '+({admin:'Admin',giangvien:'Giảng viên',sinhvien:'Sinh viên'}[user.role]);
 const logout=document.createElement('button');logout.textContent='Đăng xuất';logout.className='secondary';
 logout.onclick=async()=>{try{await api('/auth/logout','POST');}finally{sessionStorage.removeItem('doan_token');location.reload();}};
 box.append(logout);document.querySelector('aside').append(box);
}
