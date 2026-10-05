<?php
namespace Database\Seeders;
use App\Models\{SinhVien,DeTai,User};
class DatabaseSeeder extends \Illuminate\Database\Seeder {
    public function run(): void {
        $demoPassword=password_hash('Demo@12345',PASSWORD_BCRYPT);
        foreach ([['SV001','Nguyễn Văn An','an@example.com'],['SV002','Trần Thị Bình','binh@example.com'],['SV003','Lê Minh Châu','chau@example.com']] as [$code,$name,$email]) SinhVien::firstOrCreate(['ma_sinh_vien'=>$code],['ho_ten'=>$name,'email'=>$email,'lop'=>'CNTT01','nganh'=>'Công nghệ thông tin']);
        foreach ([['DT001','Hệ thống quản lý đồ án theo SOA'],['DT002','Ứng dụng thư viện trực tuyến'],['DT003','Nhận diện hình ảnh bằng AI']] as [$code,$name]) DeTai::firstOrCreate(['ma_de_tai'=>$code],['ten_de_tai'=>$name,'mo_ta'=>'Xây dựng, kiểm thử và triển khai hệ thống.','giang_vien'=>'TS. Nguyễn Minh','so_luong_sinh_vien'=>2,'trang_thai'=>'con_trong']);
        if (app()->environment(['local','testing'])) {
            User::firstOrCreate(['username'=>'admin'],['name'=>'Quản trị viên','role'=>'admin','password'=>$demoPassword]);
            $teacher=User::firstOrCreate(['username'=>'giangvien'],['name'=>'TS. Nguyễn Minh','role'=>'giangvien','password'=>$demoPassword]);
            $student=SinhVien::where('ma_sinh_vien','SV001')->first();
            User::firstOrCreate(['username'=>'sinhvien'],['name'=>$student->ho_ten,'role'=>'sinhvien','sinh_vien_id'=>$student->id,'password'=>$demoPassword]);
            DeTai::whereIn('ma_de_tai',['DT001','DT002','DT003'])->whereNull('giang_vien_id')->update(['giang_vien_id'=>$teacher->id]);
        }
    }
}
