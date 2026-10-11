<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\DangKyService;
use App\Http\Requests\DangKyRequest;
class DangKyController extends \Laravel\Lumen\Routing\Controller {
    private $service;
    public function __construct(DangKyService $service) { $this->service=$service; }
    public function index(Request $request) { return response()->json(['success'=>true,'data'=>$this->service->getAll($request->only(['q','trang_thai','sinh_vien_id','de_tai_id','giang_vien_id']))]); }
    public function show($id) { return response()->json(['success'=>true,'data'=>$this->service->getById($id)]); }
    public function store(Request $request) {
        $data=$this->validate($request,DangKyRequest::rules());
        return response()->json(['success'=>true,'data'=>$this->service->create($data)],201);
    }
    public function update(Request $request,$id) {
        $this->service->getById($id);
        $data=$this->validate($request,DangKyRequest::rules($id));
        return response()->json(['success'=>true,'data'=>$this->service->update($id,$data)]);
    }
    public function destroy($id) { $this->service->delete($id); return response()->json(['success'=>true,'message'=>'Đã xoá thành công.']); }
    public function grades(Request $request, $id) {
        $data = $this->validate($request, [
            'diem_huong_dan'=>'required|numeric|between:0,10|regex:/^\d+(\.\d{1,2})?$/',
            'diem_phan_bien'=>'required|numeric|between:0,10|regex:/^\d+(\.\d{1,2})?$/',
            'diem_bao_ve'=>'required|numeric|between:0,10|regex:/^\d+(\.\d{1,2})?$/',
        ]);
        return response()->json(['success'=>true,'data'=>(new \App\Services\DiemService())->save($id, $data)]);
    }
    public function clearGrades($id) {
        return response()->json(['success'=>true,'data'=>(new \App\Services\DiemService())->save($id, ['diem_huong_dan'=>null,'diem_phan_bien'=>null,'diem_bao_ve'=>null])]);
    }
}
