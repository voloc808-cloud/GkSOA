# Auth service

Ứng dụng Lumen hiện có được chuyển nguyên vẹn vào service này, gồm JWT, User, migrations, seeders, giao diện demo và tests.

Chạy `composer install` trong thư mục này; cấu hình `.env`, sau đó chạy `php -S 127.0.0.1:8001 -t public`.
API hiện có: POST /login (hoặc /), GET /auth, GET /hello. GET / hiển thị demo.
Kiểm tra: `php vendor/phpunit/phpunit/phpunit`.
Tài liệu thực hành cũ: ../../docs/auth-lab.md (các lệnh chạy trong thư mục service).
