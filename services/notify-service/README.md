# notify-service

Thông báo và xử lý tác vụ gửi bất đồng bộ.

Bộ khung Lumen 10 độc lập. Hiện có GET /health; chưa triển khai nghiệp vụ.

Chạy trong thư mục service: composer install, sao chép .env.example thành .env, sau đó php -S 127.0.0.1:8008 -t public.

Database thuộc riêng service. Không truy vấn database của service khác.
