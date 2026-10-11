import {api} from './api.js';
import {user} from './auth.js';
const fields={diem_huong_dan:'Điểm hướng dẫn (30%)',diem_phan_bien:'Điểm phản biện (30%)',diem_bao_ve:'Điểm bảo vệ (40%)'};
export async function openGrades(id,refresh){
 const row=await api('/dangky/'+id),$=s=>document.querySelector(s);
 $('#modal-title').textContent='Chấm điểm đồ án';
 $('#fields').innerHTML='<p>Quy tắc demo: thang 10, hướng dẫn 30% + phản biện 30% + bảo vệ 40%. Đạt từ 5 điểm.</p>'+Object.entries(fields).map(([key,label])=>'<label>'+label+'<input name="'+key+'" type="number" min="0" max="10" step="0.01" required></label>').join('')+'<p id="grade-preview" role="status"></p>';
 for(const key of Object.keys(fields))$('#fields [name='+key+']').value=row[key]??'';
 const preview=()=>{
  const values=Object.keys(fields).map(key=>$('#fields [name='+key+']').value);
  if(values.some(v=>v===''||!Number.isFinite(Number(v))||Number(v)<0||Number(v)>10)){$('#grade-preview').textContent='Nhập đủ ba điểm hợp lệ để tính tổng kết.';return;}
  const hundredths=values.map(v=>Math.round(Number(v)*100));
  const total=Math.round((hundredths[0]*30+hundredths[1]*30+hundredths[2]*40)/100)/100;
  $('#grade-preview').textContent='Điểm tổng kết: '+total.toFixed(2)+' · '+(total>=5?'Đạt':'Không đạt');
 };
 $('#fields').oninput=preview;preview();
 $('#form-error').textContent='';$('#save').hidden=false;$('#save').disabled=false;
 $('#editor').onsubmit=async event=>{
  event.preventDefault();$('#save').disabled=true;
  const data=Object.fromEntries(Object.keys(fields).map(key=>[key,Number($('#fields [name='+key+']').value)]));
  try{await api('/dangky/'+id+'/diem','PUT',data);$('#modal').close();await refresh();}
  catch(error){$('#form-error').textContent=error.message;}finally{$('#save').disabled=false;}
 };
 $('#modal').showModal();
}
export function canGrade(row){return row?.trang_thai==='da_duyet'&&['admin','giangvien'].includes(user?.role);}
