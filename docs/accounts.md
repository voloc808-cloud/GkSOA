# Tài khoản và cấp quyền

Đăng nhập frontend tại http://127.0.0.1:8080. Admin mở **Tài khoản** ở menu bên trái để xem danh sách, cấp tài khoản, sửa vai trò, đặt lại mật khẩu hoặc xóa tài khoản. Giảng viên và sinh viên không có quyền truy cập API quản lý tài khoản, kể cả khi tự gửi HTTP request.

Tài khoản lưu ở bảng `users`; mật khẩu được băm, không thể xem lại. Token có thời hạn 8 giờ; đăng xuất thu hồi token hiện tại, sửa tài khoản thu hồi mọi token của tài khoản đó.

## Khởi tạo admin đầu tiên

Thêm vào `backend/.env` với mật khẩu riêng của bạn:

```dotenv
ADMIN_USERNAME=admin
ADMIN_PASSWORD=your-own-password
```

Sau đó chạy từ thư mục dự án. Với SQLite, các cờ `-d` dưới đây nạp driver và mbstring cho từng lệnh, không cần sửa php.ini:

```powershell
php -d extension=pdo_sqlite -d extension=mbstring backend/artisan migrate --force
php -d extension=pdo_sqlite -d extension=mbstring backend/artisan db:seed --class=Database\Seeders\AuthSeeder --force
```

AuthSeeder chỉ tạo admin nếu tên đăng nhập chưa tồn tại, không đặt lại mật khẩu tài khoản có sẵn. DatabaseSeeder ở APP_ENV=local/testing tạo tài khoản demo admin, giangvien, sinhvien với mật khẩu Demo@12345; xem [hướng dẫn demo và phân quyền](auth-roles.md). Với SQLite demo, dùng cùng DB_CONNECTION và DB_DATABASE như tiến trình demo khi chạy hai lệnh trên.

Với MySQL, thay `extension=pdo_sqlite` bằng `extension=pdo_mysql`. Nếu extension đã được bật trong php.ini, có thể bỏ cờ `-d`.

## Admin cấp tài khoản

1. Đăng nhập bằng tài khoản admin rồi mở **Tài khoản → Cấp tài khoản**.
2. Nhập tên đăng nhập, họ tên, mật khẩu ít nhất 8 ký tự và chọn **Giảng viên** hoặc **Sinh viên**.
3. Với sinh viên, chọn hồ sơ sinh viên tương ứng. Tạo hồ sơ ở mục **Sinh viên** trước nếu chưa có. Mỗi hồ sơ chỉ liên kết một tài khoản.
4. Lưu và cung cấp tên đăng nhập, mật khẩu cho người dùng.

Admin có toàn bộ quyền thêm, sửa, xóa sinh viên, đề tài, đăng ký và tài khoản giảng viên/sinh viên. Giảng viên chỉ tạo đề tài mới của mình và xem sinh viên/đăng ký thuộc đề tài của mình; không sửa, xóa đề tài hoặc duyệt đăng ký. Sinh viên xem đề tài có giảng viên phụ trách và đăng ký cho chính mình; không sửa, hủy hoặc xóa đăng ký. Không có API tự đăng ký tài khoản hoặc tự nâng quyền.

Admin chuyển sinh viên sang đề tài khác tại **Đăng ký → Sửa → Đề tài**, rồi lưu. Backend giữ nguyên sinh viên, kiểm tra đề tài đích còn chỗ và không có đăng ký trùng, cập nhật số chỗ của đề tài cũ và mới trong cùng transaction. Nếu đề tài đích đã đầy hoặc ngừng nhận, đăng ký đang hoạt động không thể chuyển sang. Sinh viên có thể bấm **Đăng ký** trực tiếp trên danh sách đề tài còn chỗ.

Với đề tài đã có trước khi thêm đăng nhập, admin mở **Đề tài → Sửa → Tài khoản giảng viên phụ trách** để liên kết người phụ trách. Đề tài mới do giảng viên tạo sẽ tự liên kết tài khoản đang đăng nhập.

API qua gateway: `POST /api/auth/login`, `GET /api/auth/me`, `POST /api/auth/logout`, `GET/POST /api/users`, `GET/PUT/DELETE /api/users/{id}`. Login nhận `username`, `password`; các API khác dùng `Authorization: Bearer <access_token>`.
