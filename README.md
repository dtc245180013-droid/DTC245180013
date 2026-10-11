# Hệ Thống Quản Lý Phòng Gym / Fitness

Hệ thống quản lý phòng tập Gym toàn diện triển khai với kiến trúc microservices trên nền tảng Docker & Docker Compose.

## 1. Cấu Trúc Thư Mục
```
gym/
├── db/
│   └── init.sql              # Khởi tạo CSDL, 7 bảng, index, seed dữ liệu mẫu
├── nginx/
│   ├── nginx.conf            # Reverse Proxy, SSL TLS 1.2/1.3, Security Headers
│   ├── gen-cert.sh           # Script tự sinh chứng chỉ SSL tự ký
│   └── certs/
│       ├── server.crt        # Chứng chỉ công khai (SAN IP & localhost)
│       └── server.key        # Khóa riêng tư (bảo mật, không commit)
├── web/
│   ├── Dockerfile            # PHP 8.2 Apache + PDO MySQL
│   └── src/                  # Mã nguồn ứng dụng Web PHP thuần
├── evidence/                 # Bằng chứng kiểm thử các bước
├── docker-compose.yml        # Định nghĩa các dịch vụ Docker
├── .env                      # Biến môi trường bảo mật
└── .env.example              # Mẫu biến môi trường
```

## 2. Hướng Dẫn Tự Sinh Chứng Chỉ SSL Tự Ký
Hệ thống sử dụng script tự động để tạo chứng chỉ SSL X.509 với thời hạn 365 ngày và Subject Alternative Name (SAN):
```bash
# Chạy script tự sinh chứng chỉ:
chmod +x nginx/gen-cert.sh
./nginx/gen-cert.sh
```
Khóa riêng tư `server.key` được cấu hình bảo mật với quyền `chmod 600` và được khai báo bỏ qua trong `.gitignore`.

## 3. Khởi Chạy Hệ Thống
```bash
# Sao chép và cấu hình biến môi trường:
cp .env.example .env

# Khởi chạy toàn bộ hệ thống bằng Docker Compose:
docker compose up -d --build
```

## 4. Truy Cập Hệ Thống
- **Ứng dụng Web:** `https://192.168.233.128/` (hoặc `http://192.168.233.128/` - tự động chuyển hướng 301 sang HTTPS)
- **Quản trị CSDL (phpMyAdmin):** `https://192.168.233.128/phpmyadmin/`
