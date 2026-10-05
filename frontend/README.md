# Frontend quản lý đồ án
HTML/CSS/JavaScript thuần, không cần npm. Chạy `php -S 127.0.0.1:8080 -t frontend` từ gốc dự án.
Mở http://127.0.0.1:8080. API mặc định http://127.0.0.1:8000/api.
Đổi API URL trong console trình duyệt: `localStorage.setItem('doan_api_url','http://host:8000/api'); location.reload()`.
Nếu đổi origin frontend, sửa FRONTEND_URL trong gateway/.env.

Đăng nhập bằng tài khoản do admin cấp. Admin mở **Tài khoản** để tạo người dùng, cấp vai trò và đặt lại mật khẩu; giảng viên/sinh viên không thấy mục này. Khởi tạo admin đầu tiên theo [hướng dẫn](../docs/accounts.md).
