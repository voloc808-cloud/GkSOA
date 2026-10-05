# Chuyển dữ liệu hiện có sang MySQL một lần

MySQL lưu database lâu dài, không cần tạo bảng hay nhập lại SQL mỗi ngày. Mỗi lần dùng chỉ cần MySQL đang chạy và khởi động backend/frontend.

## Chuyển dữ liệu SQLite

1. Trong backend/.env, nhập đúng DB_HOST, DB_PORT, DB_USERNAME và DB_PASSWORD của kết nối MySQL Workbench. Đặt mật khẩu trong dấu nháy đơn nếu chứa ký tự đặc biệt. Giữ DB_CONNECTION=sqlite và DB_DATABASE là đường dẫn SQLite hiện có cho tới khi chuyển thành công.
2. Dừng dự án bằng Ctrl+C trong terminal khởi động. Script chuyển sẽ từ chối nếu các cổng backend đang mở.
3. Từ thư mục gốc chạy:

```powershell
.\scripts\transfer-to-mysql.ps1 -Database quanly_doan
```

Script kết nối MySQL bằng tài khoản cấu hình; tạo database nếu chưa tồn tại; từ chối database chứa dữ liệu; sao lưu SQLite; chạy migration; chuyển SINHVIEN, users, DETAI, DANGKY trong transaction; đối chiếu toàn bộ dữ liệu; sau đó mới đổi backend/.env sang MySQL.

Giữ nguyên ID, đề tài, thông tin tài khoản, mật khẩu và đăng ký. Token phiên cũ không chuyển, nên cần đăng nhập lại.
Bản sao SQLite ở backend/database/backup_before_mysql_*.sqlite; bản sao .env ở backend/.env.before-mysql-* (đều được gitignore).
Không xoá SQLite gốc. Nếu MySQL từ chối đăng nhập (1045), cấu hình và dữ liệu nguồn không thay đổi.
Nếu target đã có dữ liệu, script dừng để tránh ghi đè; chọn tên database trống khác hoặc xử lý dữ liệu hiện có trước.

4. Chạy dự án bằng MySQL:

```powershell
.\scripts\start.ps1
```

Không thêm -SQLite. Script có kiểm tra kết nối và migration; không xoá database. Seeder không đặt lại các dữ liệu đã có.

## Những lần sau

Bật MySQL (dịch vụ MySQL80 hoặc XAMPP), chạy .\scripts\start.ps1, rồi mở http://127.0.0.1:8080.
Không chạy script chuyển lần nữa và không cần chạy SQL trong Workbench. Database vẫn còn sau khi tắt dự án/máy.

Để xem dữ liệu trong Workbench:

```sql
USE quanly_doan;
SELECT * FROM DETAI;
SELECT * FROM SINHVIEN;
SELECT * FROM DANGKY;
```

Không xem/xuất cột password hay token_hash khi chia sẻ ảnh hoặc báo cáo lỗi.

