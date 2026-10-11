# Tính điểm đồ án

Quy tắc giả định phục vụ demo, không phải quy định chính thức của khoa:

- Thang điểm 0–10, tối đa hai chữ số thập phân; phải nhập đủ ba điểm.
- Tổng kết = hướng dẫn × 30% + phản biện × 30% + bảo vệ × 40%.
- Làm tròn tổng kết hai chữ số; xét đạt từ 5 trên tổng kết đã làm tròn.
- Chỉ chấm đăng ký `da_duyet`. Admin và giảng viên phụ trách được nhập/sửa/xóa điểm; sinh viên chỉ xem đăng ký của mình.
- Chuyển đề tài hoặc chuyển sang trạng thái khác `da_duyet` xóa cả ba điểm cũ. Xóa đăng ký cũng xóa điểm đi kèm.

## Sử dụng

Chạy `php backend/artisan migrate` để thêm ba cột nullable vào DANGKY (hoặc khởi động lại bằng `scripts/start.ps1`, script tự migrate). Không cần sửa .env. Không import lại SQL vào database đang có dữ liệu.

Vào **Đăng ký**, admin duyệt đăng ký rồi chọn **Chấm điểm**. Giảng viên phụ trách cũng thấy nút này khi đăng ký đã duyệt. Nhập 8, 7, 9 sẽ có tổng kết 8.10, Đạt. Có thể xem ba điểm ở **Chi tiết**, sửa bằng **Chấm điểm** hoặc **Xóa điểm**. Sinh viên thấy cột tổng kết và kết quả trong Đăng ký của tôi.

## API qua Gateway

- `PUT /api/dangky/{id}/diem`: Bearer token của admin/giảng viên phụ trách, JSON `{"diem_huong_dan":8,"diem_phan_bien":7,"diem_bao_ve":9}`.
- `DELETE /api/dangky/{id}/diem`: xóa điểm, giữ đăng ký.
- `GET /api/dangky` và `GET /api/dangky/{id}`: trả ba điểm, `diem_tong_ket`, `ket_qua` (`chua_cham`, `dat`, `khong_dat`). Tổng kết và kết quả được tính trên backend, không nhận từ client.
- 401 chưa đăng nhập, 403 không có quyền ghi điểm, 404 bản ghi không tồn tại/ngoài phạm vi, 409 đăng ký chưa duyệt hoặc vừa thay đổi, 422 điểm không hợp lệ.

Chức năng nằm trong Registration service (:8004), Gateway chỉ proxy. Ba cột `diem_huong_dan`, `diem_phan_bien`, `diem_bao_ve` có kiểu DECIMAL(4,2); tổng kết tính từ điểm thành phần, không lưu trùng dữ liệu.

## Nội dung bổ sung báo cáo

Thêm yêu cầu quản lý điểm, công thức demo và ma trận quyền; bổ sung ba cột vào mô tả DANGKY/ERD; bổ sung trách nhiệm tính điểm cho dịch vụ đăng ký, API điểm và luồng kiểm tra quyền → kiểm tra dữ liệu → transaction lưu điểm → tính tổng kết → trả JSON. Giữ sơ đồ ba dịch vụ. Phần kiểm thử ghi kết quả thực tế cho tính đúng điểm, ngưỡng đạt, điểm sai, thiếu điểm, sai quyền, chưa duyệt, xóa điểm và chuyển đề tài.

Kiểm thử biệt lập: `php -d extension=C:/PHP/ext/php_pdo_sqlite.dll -d extension=C:/PHP/ext/php_mbstring.dll backend/tests/grades.php`.
