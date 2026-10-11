-- Alternative to migrations: import this file into MySQL 8.
CREATE DATABASE IF NOT EXISTS quanly_doan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE quanly_doan;
CREATE TABLE SINHVIEN (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 ma_sinh_vien VARCHAR(30) NOT NULL UNIQUE,
 ho_ten VARCHAR(150) NOT NULL,
 email VARCHAR(150) NOT NULL UNIQUE,
 lop VARCHAR(50) NOT NULL,
 nganh VARCHAR(150) NOT NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL
) ENGINE=InnoDB;
CREATE TABLE DETAI (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 ma_de_tai VARCHAR(30) NOT NULL UNIQUE,
 ten_de_tai VARCHAR(255) NOT NULL,
 mo_ta TEXT NULL,
 giang_vien VARCHAR(150) NOT NULL,
 so_luong_sinh_vien INT UNSIGNED NOT NULL,
 trang_thai VARCHAR(20) NOT NULL DEFAULT 'con_trong',
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL
) ENGINE=InnoDB;
CREATE TABLE DANGKY (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 sinh_vien_id BIGINT UNSIGNED NOT NULL,
 de_tai_id BIGINT UNSIGNED NOT NULL,
 ngay_dang_ky DATETIME NOT NULL,
 trang_thai VARCHAR(20) NOT NULL DEFAULT 'cho_duyet',
 diem_huong_dan DECIMAL(4,2) NULL,
 diem_phan_bien DECIMAL(4,2) NULL,
 diem_bao_ve DECIMAL(4,2) NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 CONSTRAINT fk_dangky_student FOREIGN KEY(sinh_vien_id) REFERENCES SINHVIEN(id) ON DELETE RESTRICT,
 CONSTRAINT fk_dangky_topic FOREIGN KEY(de_tai_id) REFERENCES DETAI(id) ON DELETE RESTRICT,
 UNIQUE KEY unique_student_topic(sinh_vien_id,de_tai_id),
 KEY topic_status(de_tai_id,trang_thai)
) ENGINE=InnoDB;
CREATE TABLE users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 username VARCHAR(50) NOT NULL UNIQUE,
 name VARCHAR(150) NOT NULL,
 password VARCHAR(255) NOT NULL,
 role VARCHAR(20) NOT NULL,
 sinh_vien_id BIGINT UNSIGNED NULL UNIQUE,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 CONSTRAINT fk_user_student FOREIGN KEY(sinh_vien_id) REFERENCES SINHVIEN(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
CREATE TABLE access_tokens (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 token_hash VARCHAR(64) NOT NULL UNIQUE,
 expires_at DATETIME NOT NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 CONSTRAINT fk_token_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
ALTER TABLE DETAI ADD COLUMN giang_vien_id BIGINT UNSIGNED NULL,
 ADD CONSTRAINT fk_topic_teacher FOREIGN KEY(giang_vien_id) REFERENCES users(id) ON DELETE RESTRICT;
INSERT INTO SINHVIEN (ma_sinh_vien,ho_ten,email,lop,nganh,created_at,updated_at) VALUES
 ('SV001','Nguyễn Văn An','an@example.com','CNTT01','Công nghệ thông tin',NOW(),NOW()),
 ('SV002','Trần Thị Bình','binh@example.com','CNTT01','Công nghệ thông tin',NOW(),NOW()),
 ('SV003','Lê Minh Châu','chau@example.com','CNTT01','Công nghệ thông tin',NOW(),NOW());
INSERT INTO DETAI (ma_de_tai,ten_de_tai,mo_ta,giang_vien,so_luong_sinh_vien,trang_thai,created_at,updated_at) VALUES
 ('DT001','Hệ thống quản lý đồ án theo SOA','Xây dựng, kiểm thử và triển khai hệ thống.','TS. Nguyễn Minh',2,'con_trong',NOW(),NOW()),
 ('DT002','Ứng dụng thư viện trực tuyến','Xây dựng, kiểm thử và triển khai hệ thống.','TS. Nguyễn Minh',2,'con_trong',NOW(),NOW()),
 ('DT003','Nhận diện hình ảnh bằng AI','Xây dựng, kiểm thử và triển khai hệ thống.','TS. Nguyễn Minh',2,'con_trong',NOW(),NOW());

