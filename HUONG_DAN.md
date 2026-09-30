# Bài thực hành Router, Middleware và JWT với Lumen

Dự án triển khai bằng Lumen 10, framework thuộc hệ sinh thái Laravel. Đây là bản Lumen, không phải hai ứng dụng Laravel và Lumen riêng biệt.

## 1. Chuẩn bị và chạy

Thư viện `firebase/php-jwt` đã được khai báo trong composer.json và khóa phiên bản trong composer.lock. Cài lại dependencies khi chuyển máy:

```powershell
composer install
```

Lệnh thêm thư viện khi làm từ đầu: `composer require firebase/php-jwt`. Tài liệu: https://github.com/googleapis/php-jwt

Nếu chưa có `.env`, sao chép `.env.example` thành `.env`. Giữ các cấu hình khác, sửa/thêm các dòng sau để chạy SQLite tại máy hiện tại:

```dotenv
APP_ENV=local
APP_DEBUG=false
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite
DB_DATABASE="C:/Users/MSI GAMING/lumen-api/database/database.sqlite"
JWT_SECRET=<thay-bang-chuoi-ngau-nhien-ben-duoi>
JWT_ISSUER=lumen-api
JWT_TTL=3600
```

Sinh secret bằng lệnh sau, rồi chép kết quả vào JWT_SECRET (không dùng nguyên chuỗi placeholder):

```powershell
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Tạo database nếu chưa có và chạy migration, seed:

```powershell
if (!(Test-Path database/database.sqlite)) { New-Item database/database.sqlite -ItemType File }
php -d extension=pdo_sqlite -d extension=mbstring artisan migrate --seed
php -d extension=pdo_sqlite -d extension=mbstring -S localhost:8000 -t public
```

Các cờ `-d` nạp extension cho từng lệnh, không sửa php.ini. Nếu đã bật extension trong php.ini thì bỏ các cờ này. Máy khác cần sửa đường dẫn DB_DATABASE. Nếu dùng MySQL, tạo database trước, cấu hình DB_CONNECTION=mysql và DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD, bật pdo_mysql rồi chạy migration.

Tài khoản mẫu chỉ seed trong môi trường local/testing: **student / 123456**. Seeder không ghi đè tài khoản đã tồn tại.

## 2. API và Postman

Mở `http://localhost:8000/` trên trình duyệt, nhập `student / 123456`, bấm **Đăng nhập**. Trang tự chuyển mật khẩu sang Base64, hiển thị JWT và cho phép bấm **Xác thực /auth**, **Hello World**. Kết quả hiện bên dưới. Token chỉ giữ trong bộ nhớ trang; tải lại trang sẽ xóa token ở client. Mở phần cấu trúc JWT để xem header/payload.

| Method | URL | Chức năng | JWT |
| --- | --- | --- | --- |
| GET | `/` | Trang đăng nhập và thử API trên trình duyệt | Không |
| POST | `/` hoặc `/login` | Kiểm tra tài khoản và cấp token | Không |
| GET | `/auth` | Xác thực và trả thông tin người dùng | Có |
| GET | `/hello` | Trả `{"message":"Hello World"}` | Có |

Import `postman/Lumen-JWT.postman_collection.json`. Biến baseUrl mặc định là `http://localhost:8000`. Chạy Login trước; script tự lưu access_token vào biến token. Hai request tiếp theo tự gửi Bearer token. Request cuối kiểm tra truy cập không có token.

Đăng nhập bằng POST `http://localhost:8000/`, chọn Body → raw → JSON:

```json
{"userName":"student","password":"MTIzNDU2"}
```

`MTIzNDU2` là Base64 của `123456`. Gửi `Content-Type: application/json` và `Accept: application/json`. Kết quả gồm access_token, token_type, expires_in và user. Dùng Authorization → Bearer Token để gọi `/auth` và `/hello`.

Đề cho phép Base64 hoặc MD5; bài này chọn Base64, không nhận mật khẩu MD5. Base64 là mã hóa biểu diễn có thể đảo ngược, không bảo vệ mật khẩu trên đường truyền; ứng dụng thực tế cần HTTPS. Server giải mã Base64 và kiểm tra bcrypt, không lưu mật khẩu rõ hoặc MD5.

Các mã phản hồi: 200 thành công; 422 thiếu/sai dạng dữ liệu hoặc Base64; 401 sai tài khoản, thiếu JWT, sai chữ ký, hết hạn hoặc token đã bị thay thế. Không đưa JWT vào URL.

## 3. Giải thích để báo cáo

Router trong `routes/web.php` ánh xạ method + URL đến controller, gắn middleware `jwt` cho `/auth`, `/hello`. `bootstrap/app.php` bật Eloquent, Facades, cấu hình JWT và đăng ký alias middleware.

`AuthController@login` kiểm tra input → giải mã Base64 → tìm UserName → kiểm tra bcrypt → ký JWT bằng HS256 → lưu SHA-256 của JWT → trả token cho client.

`JwtAuthenticate` lấy header Authorization → kiểm tra chữ ký với thuật toán cố định HS256, thời hạn và issuer → tìm user theo sub → đối chiếu dấu băm token trong database → gắn user vào request → cho chạy controller. Lỗi xác thực trả 401 trước khi chạy Hello World. Lỗi cấu hình secret là lỗi server, không phải lỗi token của client.

JWT gồm `header.payload.signature`. Header khai báo typ và alg; payload có iss (nơi phát hành), sub (IdUser), iat (thời điểm cấp), nbf (bắt đầu hiệu lực), exp (hết hạn), jti (mã token ngẫu nhiên). Thời gian dùng Unix timestamp giây. Chữ ký HMAC-SHA256 giúp phát hiện sửa đổi; nội dung payload không được mã hóa bí mật. Không đưa password vào token.

Script Postman của Login giải mã header/payload vào Postman Console để quan sát và kiểm tra có ba phần. Giải mã payload không chứng minh token hợp lệ; server `/auth` mới kiểm tra chữ ký và trạng thái token. Không chia sẻ secret/token thật lên website giải mã.

| Cột bảng users | Kiểu | Ý nghĩa |
| --- | --- | --- |
| IdUser | INT, primary key, tự tăng | Định danh |
| UserName | VARCHAR(255), unique | Tên đăng nhập |
| Password | VARCHAR(255) | Bcrypt của mật khẩu gốc |
| Token | VARCHAR(255), nullable | SHA-256 của JWT (64 ký tự hex) |

Cột Token giữ dấu băm vì JWT có thể dài hơn 255 ký tự. Client giữ JWT gốc để gửi request. Mỗi user chỉ có một token hiện hành: đăng nhập lại vô hiệu token trước, kể cả khi chưa hết hạn. Thiết kế này có kiểm tra database mỗi request, không hoàn toàn stateless. Nếu giảng viên yêu cầu lưu JWT nguyên văn, cần thống nhất đổi Token sang TEXT.

## 4. Kiểm thử

```powershell
php -d extension=pdo_sqlite -d extension=mbstring vendor/bin/phpunit
```

Tests dùng SQLite trong bộ nhớ, không sửa database thực. Bao gồm đăng nhập đúng/sai, thiếu dữ liệu, Base64 lỗi, truy cập Hello World, token thiếu/hỏng/bị sửa/hết hạn/sai issuer/chưa có hiệu lực/sai thuật toán, thay thế token và user đã bị xóa.

Kết quả tại máy triển khai: 6 tests, 38 assertions thành công. PHP 8.4 phát cảnh báo deprecated từ mã kiểm thử Lumen 10 trong vendor; không chỉnh sửa vendor để che cảnh báo.

Phạm vi là bài thực hành; trước khi triển khai công khai cần bổ sung giới hạn thử đăng nhập và quản lý khóa/HTTPS phù hợp.
