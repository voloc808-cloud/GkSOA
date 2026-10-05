# Hệ thống quản lý đồ án tốt nghiệp — SOA

Frontend HTML/CSS/JavaScript → Laravel Gateway → ba dịch vụ Lumen → MySQL `quanly_doan`.
Có CRUD sinh viên, đề tài và đăng ký; tìm kiếm, lọc trạng thái, thống kê, duyệt/từ chối/huỷ đăng ký.

## Cấu trúc

- `backend/`: Lumen 10, Controllers, Requests, Services, Models, migrations, seeders, tests.
- `gateway/`: Laravel 12, proxy HTTP/JSON và dashboard.
- `frontend/`: giao diện tiếng Việt, responsive, API client.
- `database/quanly_doan.sql`: schema MySQL và dữ liệu mẫu.
- `postman/DoAn.postman_collection.json`: 30 request có assertions, chạy theo thứ tự.
- `scripts/start.ps1`: chạy gateway, ba service và frontend.
- `services/auth-service/`: ứng dụng JWT cũ được giữ lại, chưa tích hợp vào API đồ án.
- Các khung dịch vụ nhà thuốc còn lại là mã cũ, không nằm trong luồng chạy đồ án.

## Yêu cầu và cài đặt

PHP >= 8.4 (theo composer.lock của gateway), Composer 2, MySQL 8. Bật `mbstring`, `fileinfo`, `pdo_mysql`, `openssl`; `curl` và `zip` giúp Composer cài nhanh hơn.

Từ gốc dự án:

```powershell
cd backend
composer install
Copy-Item .env.example .env
cd ../gateway
composer install
Copy-Item .env.example .env
php artisan key:generate
cd ..
```

Sửa `backend/.env`: DB_HOST, DB_PORT, DB_DATABASE=quanly_doan, DB_USERNAME, DB_PASSWORD.
Tạo database bằng MySQL:

```sql
CREATE DATABASE quanly_doan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Chạy migration và seeder:

```powershell
php backend/artisan migrate
php backend/artisan db:seed
```

Seeder có thể chạy lại, không xoá dữ liệu. Xem SQL thay thế bên dưới.

## Khởi động

```powershell
./scripts/start.ps1
```

Script kiểm tra dependencies/cổng, migrate + seed rồi chạy năm tiến trình PHP ẩn. Ctrl+C dừng các tiến trình do script tạo.

| Dịch vụ | URL | SERVICE_MODULE |
| --- | --- | --- |
| Laravel Gateway | http://127.0.0.1:8000/api | — |
| Student Lumen | http://127.0.0.1:8002/api/sinhvien | sinhvien |
| Topic Lumen | http://127.0.0.1:8003/api/detai | detai |
| Registration Lumen | http://127.0.0.1:8004/api/dangky | dangky |
| Frontend | http://127.0.0.1:8080 | — |

Ba tiến trình Lumen dùng cùng bộ mã, chỉ công bố route thuộc module của mình. Chạy thủ công trong ba terminal riêng:

```powershell
# Terminal 1, từ gốc dự án
$env:SERVICE_MODULE='sinhvien'
php -S 127.0.0.1:8002 -t backend/public
# Terminal 2
$env:SERVICE_MODULE='detai'
php -S 127.0.0.1:8003 -t backend/public
# Terminal 3
$env:SERVICE_MODULE='dangky'
$env:STUDENT_SERVICE_URL='http://127.0.0.1:8002'
$env:TOPIC_SERVICE_URL='http://127.0.0.1:8003'
php -S 127.0.0.1:8004 -t backend/public
# Terminal 4
php -S 127.0.0.1:8000 -t gateway/public
# Terminal 5
php -S 127.0.0.1:8080 -t frontend
```

Nếu PHP chưa bật extensions trên Windows, thêm `-d extension=C:/PHP/ext/php_mbstring.dll -d extension=C:/PHP/ext/php_pdo_mysql.dll -d extension=C:/PHP/ext/php_fileinfo.dll` trước `-S` hoặc `artisan`. Script tự nạp các DLL này từ thư mục ext cạnh php.exe.

Có thể chạy bản demo không cần MySQL: `./scripts/start.ps1 -SQLite`. SQLite dùng file riêng `backend/database/demo.sqlite`; đây là chế độ demo, kiểm thử khoá đồng thời phải chạy trên MySQL.

## Danh sách API

Base URL: `http://127.0.0.1:8000/api`.

| Method | Endpoint | Chức năng |
| --- | --- | --- |
| GET | /dashboard | Tổng sinh viên, đề tài, đăng ký, đề tài còn chỗ |
| GET | /sinhvien?q=SV001 | Danh sách/tìm mã hoặc tên |
| GET | /sinhvien/{id} | Chi tiết |
| POST | /sinhvien | Thêm |
| PUT | /sinhvien/{id} | Cập nhật đầy đủ trường |
| DELETE | /sinhvien/{id} | Xoá |
| GET | /detai?q=SOA&trang_thai=con_trong | Danh sách/tìm kiếm/lọc |
| GET | /detai/{id} | Chi tiết |
| POST | /detai | Thêm |
| PUT | /detai/{id} | Cập nhật đầy đủ trường |
| DELETE | /detai/{id} | Xoá |
| GET | /dangky | Danh sách |
| GET | /dangky?sinh_vien_id=1 | Đăng ký của sinh viên |
| GET | /dangky?de_tai_id=1 | Đăng ký của đề tài |
| GET | /dangky?trang_thai=cho_duyet | Lọc trạng thái |
| GET | /dangky/{id} | Chi tiết |
| POST | /dangky | Tạo từ sinh_vien_id, de_tai_id |
| PUT | /dangky/{id} | Đổi trang_thai |
| DELETE | /dangky/{id} | Xoá |

Ví dụ thêm sinh viên:

```json
{"ma_sinh_vien":"SV004","ho_ten":"Phạm Minh","email":"minh@example.com","lop":"CNTT01","nganh":"Công nghệ thông tin"}
```

Ví dụ thêm đề tài:

```json
{"ma_de_tai":"DT004","ten_de_tai":"Website học trực tuyến","mo_ta":"REST API","giang_vien":"GV Nguyễn Minh","so_luong_sinh_vien":2,"trang_thai":"con_trong"}
```

Đăng ký: `{"sinh_vien_id":1,"de_tai_id":1}`. Đổi trạng thái: `{"trang_thai":"da_duyet"}`; huỷ: `{"trang_thai":"da_huy"}`.

Response: `{"success":true,"data":...}`; lỗi: `{"success":false,"message":"...","errors":{...}}` (errors có ở lỗi validation).
HTTP 200 đọc/sửa/xoá, 201 tạo, 404 không tồn tại, 405 sai method, 409 xung đột nghiệp vụ, 422 dữ liệu sai, 500 lỗi hệ thống, 502 phản hồi upstream sai, 503 dịch vụ không sẵn sàng.

## Quy tắc nghiệp vụ

- Mã sinh viên/email/mã đề tài duy nhất. Unique cặp sinh viên–đề tài ở database.
- Chờ duyệt và đã duyệt chiếm chỗ. Từ chối và đã huỷ trả lại chỗ.
- Cặp đã huỷ/từ chối vẫn không thể tạo lại; cập nhật bản ghi cũ để khôi phục, hoặc xoá rồi tạo mới.
- Không nhận đăng ký khi ngừng nhận/hết chỗ; khôi phục đăng ký cũng kiểm tra sức chứa.
- Trạng thái `da_du` được tính từ số đăng ký hoạt động. Tăng sức chứa/huỷ/xoá đăng ký cập nhật trạng thái; `ngung_nhan` giữ nguyên tới khi quản trị mở lại.
- Không giảm sức chứa xuống dưới số đăng ký hoạt động.
- Xoá sinh viên/đề tài có đăng ký trả 409; xoá các đăng ký liên quan trước.
- Transaction và khoá hàng MySQL bảo vệ sức chứa khi có nhiều yêu cầu đồng thời.

## Kiểm thử

```powershell
php -d extension=C:/PHP/ext/php_pdo_sqlite.dll -d extension=C:/PHP/ext/php_mbstring.dll backend/tests/run.php
```

116 assertions, SQLite in-memory, không đụng database đang dùng. Trên Linux với extensions bật sẵn: `php backend/tests/run.php`.

Kiểm tra lỗi upstream gateway: `php gateway/tests/run.php` (bật mbstring/fileinfo). Khi năm tiến trình đang chạy: `node scripts/test-http.mjs` chạy 30 request từ collection qua HTTP, cộng kiểm tra CORS và giới hạn route. Bộ HTTP tạo rồi xoá dữ liệu thử; không chạy với dữ liệu sản xuất.

Import `postman/DoAn.postman_collection.json`, đặt `base_url`, chạy Collection Runner theo thứ tự. Collection tạo mã riêng mỗi lượt, lưu ID vào collection variables, kiểm tra CRUD/lỗi và xoá dữ liệu kiểm thử cuối lượt. Nếu dừng giữa chừng, xoá các bản ghi có mã PMSV/PMDT thủ công.

## SQL thay thế migration

Import `database/quanly_doan.sql` vào database trống thay vì migrate + seed. Không dùng cả hai cách trên cùng schema: SQL không tạo bảng migrations. Nếu đã import SQL, chạy thủ công từng dịch vụ; script start dành cho database quản lý bởi migrations.

## Kiến trúc và phạm vi

Xem [docs/architecture.md](docs/architecture.md) để biết sơ đồ SOA và ranh giới transaction.
Hệ thống có đăng nhập và ba vai trò admin, giảng viên, sinh viên. Xem [hướng dẫn đăng nhập và phân quyền](docs/auth-roles.md) để dùng tài khoản mẫu, cấp tài khoản và kiểm tra bằng Postman. Auth JWT cũ vẫn được giữ riêng.
