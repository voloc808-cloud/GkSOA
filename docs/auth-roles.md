# Đăng nhập và phân quyền

Đăng nhập bằng username/password qua POST /api/auth/login. Token ngẫu nhiên 256 bit được lưu dưới dạng SHA-256 trong access_tokens, có hạn 8 giờ. Mật khẩu băm bcrypt, không xuất trong JSON. POST /api/auth/logout thu hồi token hiện tại. Đổi mật khẩu/vai trò tài khoản thu hồi tất cả token của tài khoản đó.

Cả gateway và các cổng service đều yêu cầu Bearer token cho API nghiệp vụ; health và login công khai. Gateway chuyển token qua HTTP. Auth hiện dùng cùng schema quanly_doan và công bố tại Student service (:8002); auth-service JWT cũ là mã riêng, không chạy trong luồng này.

| Chức năng | Admin | Giảng viên | Sinh viên |
| --- | --- | --- | --- |
| Xem đề tài | Tất cả | Đề tài của mình | Đề tài có giảng viên phụ trách |
| Thêm/sửa/xoá sinh viên | Có | Không | Không |
| Xem hồ sơ sinh viên | Tất cả | Sinh viên đăng ký đề tài của mình | Chỉ bản thân |
| Thêm đề tài | Có | Tạo đề tài của mình | Không |
| Sửa/xoá đề tài | Có | Không | Không |
| Tạo đăng ký | Cho mọi sinh viên | Không | Cho bản thân |
| Xem đăng ký | Tất cả | Thuộc đề tài mình | Của bản thân |
| Duyệt/từ chối đăng ký | Tất cả | Không | Không |
| Chuyển sinh viên sang đề tài khác | Có | Không | Không |
| Huỷ đăng ký | Tất cả | Không | Không |
| Xoá đăng ký | Có | Không | Không |
| Cấp/sửa/xoá tài khoản | Có | Không | Không |

Thống kê dashboard dùng dữ liệu theo quyền người đăng nhập. Giảng viên chỉ tạo đề tài mới và xem sinh viên đăng ký đề tài của mình. Admin chọn giảng viên phụ trách khi tạo/sửa đề tài, và chuyển sinh viên qua **Đăng ký → Sửa → Đề tài**. Sinh viên bấm **Đăng ký** ở danh sách đề tài còn chỗ. Đề tài chưa phân công chỉ admin quản lý.

## Cập nhật dự án hiện có

Dừng các tiến trình cũ bằng Ctrl+C trong terminal khởi động, rồi chạy từ gốc dự án:

```powershell
.\scripts\start.ps1
```

Script tự migrate + seed trước khi chạy. Dùng `-SQLite` nếu muốn chạy demo SQLite riêng.
Migration chỉ thêm bảng users, access_tokens và cột DETAI.giang_vien_id, giữ dữ liệu nghiệp vụ.
Sinh viên đang có tài khoản liên kết không thể bị xoá; xoá tài khoản liên kết trước.
SQL thay thế cũng có bảng auth; sau import vào database trống, chạy `php backend/artisan db:seed` trước khi khởi động thủ công.

## Tài khoản demo

Khi APP_ENV=local hoặc testing, seeder tạo ba tài khoản nếu chưa có:

| Tên đăng nhập | Mật khẩu mẫu | Vai trò |
| --- | --- | --- |
| admin | Demo@12345 | Admin |
| giangvien | Demo@12345 | Giảng viên |
| sinhvien | Demo@12345 | Sinh viên SV001 |

Seeder chạy lại không đổi mật khẩu đã đặt. Chỉ phân công các đề tài mẫu chưa có giảng viên; giữ các phân công hiện có.
Dùng màn hình Tài khoản của admin để cấp tài khoản thật và đổi mật khẩu. Không có đăng ký công khai hay tự chọn quyền khi login.
Đổi thông tin tài khoản đang đăng nhập sẽ đăng xuất tài khoản đó.

## API và Postman

- POST /api/auth/login: `{"username":"admin","password":"Demo@12345"}`.
- GET /api/auth/me: thông tin tài khoản hiện tại.
- POST /api/auth/logout: đăng xuất.
- CRUD /api/users và /api/users/{id}: chỉ admin.
- Tạo user: username, name, password (>=8 ký tự), role (admin/giangvien/sinhvien), sinh_vien_id nếu là sinh viên.
- PUT user cần username/name/role; bỏ password để giữ nguyên.
- Xoá/cấp lại tài khoản không xoá hồ sơ sinh viên.
- Không thể tự xoá/hạ quyền tài khoản admin đang đăng nhập.
- Chuyển đề tài sang giảng viên khác trước khi đổi vai trò/xoá giảng viên.

Import lại postman/DoAn.postman_collection.json. Variables: base_url, username, password, auth_token.
Request đăng nhập đầu collection tự lưu access_token vào auth_token; các request sau dùng Bearer token. Chạy Collection Runner theo thứ tự.
Để thử từng vai trò, đổi username và chạy request login, rồi gửi GET /dangky hoặc thử PUT/DELETE.
401 nghĩa là thiếu/sai/hết hạn token; 403 nghĩa là không đủ quyền; 404 có thể là bản ghi nằm ngoài phạm vi được xem.

## Kiểm thử

```powershell
php -d extension=C:/PHP/ext/php_pdo_sqlite.dll -d extension=C:/PHP/ext/php_mbstring.dll backend/tests/auth.php
php -d extension=C:/PHP/ext/php_pdo_sqlite.dll -d extension=C:/PHP/ext/php_mbstring.dll backend/tests/run.php
node scripts/test-http.mjs
```

Hai bộ PHP dùng SQLite in-memory, không đụng database hiện tại. HTTP runner tạo rồi xoá dữ liệu thử trên hệ thống đang chạy.

