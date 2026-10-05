# catalog-service

Danh mục thuốc, nhóm thuốc, đơn vị tính.

Bộ khung Lumen 10 độc lập. Hiện có GET /health; chưa triển khai nghiệp vụ.

Chạy trong thư mục service: composer install, sao chép .env.example thành .env, sau đó php -S 127.0.0.1:8002 -t public.

Database thuộc riêng service. Không truy vấn database của service khác.
