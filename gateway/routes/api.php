<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DoAnController;
Route::get('/health',fn()=>response()->json(['success'=>true,'data'=>['service'=>'gateway']]));
Route::post('/auth/login',fn(\Illuminate\Http\Request $r)=>app(DoAnController::class)->proxy($r,'auth','login'));
Route::post('/auth/logout',fn(\Illuminate\Http\Request $r)=>app(DoAnController::class)->proxy($r,'auth','logout'));
Route::get('/auth/me',fn(\Illuminate\Http\Request $r)=>app(DoAnController::class)->proxy($r,'auth','me'));
Route::get('/dashboard',[DoAnController::class,'dashboard']);
Route::match(['PUT','DELETE'], '/dangky/{id}/diem', fn(\Illuminate\Http\Request $r, $id)=>app(DoAnController::class)->proxy($r,'dangky',$id.'/diem'))->whereNumber('id');
Route::match(['GET','POST'],'/{resource}',[DoAnController::class,'proxy'])->where('resource','sinhvien|detai|dangky|users');
Route::match(['GET','PUT','DELETE'],'/{resource}/{id}',[DoAnController::class,'proxy'])->where('resource','sinhvien|detai|dangky|users')->whereNumber('id');
