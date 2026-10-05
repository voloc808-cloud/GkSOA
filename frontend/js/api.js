export const baseURL=localStorage.getItem('doan_api_url')||'http://127.0.0.1:8000/api';
export async function api(path,method='GET',data){
 const response=await fetch(baseURL+path,{method,headers:{Accept:'application/json',...(sessionStorage.getItem('doan_token')?{Authorization:'Bearer '+sessionStorage.getItem('doan_token')}:{}),...(data?{'Content-Type':'application/json'}:{})},...(data?{body:JSON.stringify(data)}:{}),signal:AbortSignal.timeout(15000)});
 if(response.status===401&&!path.startsWith('/auth/')){sessionStorage.removeItem('doan_token');location.reload();throw new Error('Phiên đăng nhập đã hết hạn.');}
 const body=await response.json();
 if(!response.ok||!body.success)throw new Error((body.message||'Yêu cầu thất bại.')+(body.errors?'\n'+Object.values(body.errors).flat().join('\n'):''));
 return body.data;
}
