# TÀI LIỆU HƯỚNG DẪN LÀM BÁO CÁO VÀ CHỤP ẢNH MINH CHỨNG
## Đồ án: Hệ Thống Quản Lý Phòng Gym & Thể Thao (Gym & Fitness Management System)
### Đề bài số 23 - DevOps & An Toàn Hệ Thống

---

## PHẦN I: DANH SÁCH FILE MINH CHỨNG VĂN BẢN (EVIDENCE)

Trong thư mục `~/gym/evidence/` trên máy ảo đã được lưu trữ đầy đủ 7 file minh chứng thực tế:
1. `evidence/buoc0-moitruong.txt`: Thông tin cấu hình phần cứng VM Ubuntu, phiên bản Docker, Docker Compose, Git commit ban đầu.
2. `evidence/buoc12-web-db.txt`: Cấu trúc 7 bảng CSDL, dữ liệu mẫu đã chèn (seeded data), trạng thái container Web & DB.
3. `evidence/buoc3-nginx.txt`: Chứng chỉ SSL tự ký SAN IP, cấu hình Nginx Reverse Proxy, chuyển hướng HTTP 301, 7 security headers, chứng minh các cổng 8080/8081 không bị lộ ra ngoài.
4. `evidence/buoc4-giamsat.txt`: 4 Prometheus Targets ở trạng thái UP (cAdvisor, mysqld, nginx, prometheus), danh sách 4 Grafana Dashboards tự động nạp.
5. `evidence/buoc5-logql.txt`: Kết quả thực tế 4 truy vấn LogQL qua Loki API (Nginx 5xx, Webapp error, Webapp LOGIN_FAILED, Metric count_over_time).
6. `evidence/buoc5-sucodo.txt`: Kịch bản giả lập tấn công Brute-Force, log bắt được từ container Web, Grafana Alert Rule và nội dung Webhook JSON thực tế bắn về Alert-Sink.
7. `evidence/buoc6-hardening.txt`: Kết quả cô lập 3 mạng Docker (frontend, backend internal, monitoring), kiểm tra đặc quyền tối thiểu MySQL của `gym_app`, ẩn thông tin phiên bản Apache/PHP/Nginx, trạng thái tường lửa UFW mở cổng 2849, 80, 443.

---

## PHẦN II: HƯỚNG DẪN CHỤP ẢNH MINH CHỨNG CHO BÁO CÁO (SCREENSHOT GUIDE)

Bạn mở trình duyệt web trên máy tính thật (Windows) truy cập vào địa chỉ IP của máy ảo: `https://192.168.233.128` (chọn Tiếp tục / Nâng cao nếu trình duyệt cảnh báo chứng chỉ tự ký).

### 1. Minh chứng Giao diện Ứng dụng Gym (Bước 1 & 2)
- **Ảnh 1: Trang Đăng nhập (`https://192.168.233.128/login.php`)**
  - Chụp giao diện form đăng nhập Bootstrap 5 hiện đại, badge "Gym & Fitness Management", nút Đăng nhập.
- **Ảnh 2: Bảng Điều Khiển Quản Lý - Tab Báo Cáo (`https://192.168.233.128/manager.php`)**
  - Đăng nhập tài khoản `admin` / `Gym@2026!`.
  - Chụp màn hình 4 thẻ thống kê tổng quan (Tổng hội viên, Hội viên đang hoạt động, Tổng gói tập, Doanh thu) và bảng thống kê gói tập.
- **Ảnh 3: Quản Lý Gói Tập & Modal Chỉnh Sửa Gói**
  - Chuyển sang tab "Gói Tập".
  - Bấm vào nút biểu tượng chiếc bút (Chỉnh sửa) ở một gói tập để hiện popup Modal cập nhật giá tiền hoặc tên gói. Chụp ảnh modal này.
- **Ảnh 4: Quản Lý Hội Viên & Xuất Biên Lai Thanh Toán**
  - Chuyển sang tab "Hội viên & Đăng ký".
  - Thực hiện gia hạn hoặc đăng ký gói cho 1 hội viên. Sau khi thành công, hệ thống hiện Popup "Hóa đơn / Biên lai thanh toán thành công" có dấu mộc xác nhận. Chụp ảnh modal này.
- **Ảnh 5: Giao diện Huấn Luyện Viên (`https://192.168.233.128/trainer.php`)**
  - Đăng xuất, đăng nhập tài khoản HLV: `hlv01` / `Gym@2026!`.
  - Chụp màn hình xem lịch giảng dạy của HLV và danh sách học viên trong lớp kèm nút "Điểm danh".
- **Ảnh 6: Giao diện Hội Viên (`https://192.168.233.128/member.php`)**
  - Đăng nhập tài khoản Hội viên: `hoivien01` / `Gym@2026!`.
  - Chụp màn hình xem thông tin gói tập của mình và danh sách lịch tập kèm nút "Đặt chỗ".

### 2. Minh chứng Quản trị Cơ sở dữ liệu phpMyAdmin (Bước 2)
- **Ảnh 7: phpMyAdmin (`https://192.168.233.128/phpmyadmin/`)**
  - Đăng nhập bằng `gym_app` / `GymAppP@ss2026!`.
  - Chụp màn hình danh sách 7 bảng: `bookings`, `members`, `memberships`, `packages`, `schedules`, `trainers`, `users`.

### 3. Minh chứng Nginx Reverse Proxy & SSL (Bước 3)
- **Ảnh 8: Chi tiết Chứng chỉ SSL trên Trình duyệt**
  - Bấm vào biểu tượng ổ khóa / cảnh báo bảo mật bên cạnh thanh địa chỉ URL `https://192.168.233.128`.
  - Mở chi tiết Certificate xem thông tin: Issued to `192.168.233.128`, SAN IP `192.168.233.128`, thuật toán SHA256 RSA 2048.
- **Ảnh 9: Kiểm tra Security Headers trong F12 Network**
  - Nhấn F12 trên trình duyệt -> tab Network -> tải lại trang -> bấm vào dòng request đầu tiên -> xem thẻ Headers.
  - Chụp danh sách các Headers: `Strict-Transport-Security`, `Content-Security-Policy`, `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Server: nginx` (không có X-Powered-By).

### 4. Minh chứng Giám sát Prometheus & Grafana (Bước 4)
- **Ảnh 10: Prometheus Targets (`http://192.168.233.128:9090/targets` - nếu truy cập nội bộ, hoặc chụp log)**
  - Chụp trạng thái 4 Targets: `cadvisor`, `mysqld-exporter`, `nginx-exporter`, `prometheus` đều màu xanh `UP (1/1)`.
- **Ảnh 11: Grafana - Dashboard GYM Containers (`https://192.168.233.128/grafana/`)**
  - Đăng nhập `admin` / `GymGrafanaP@ss2026!`.
  - Vào Dashboards -> chọn `GYM - Containers`. Chụp biểu đồ CPU và RAM sử dụng theo thời gian thực của 12 container.
- **Ảnh 12: Grafana - Dashboard GYM Nginx**
  - Mở dashboard `GYM - Nginx`. Chụp biểu đồ Active Connections, Request Rate, Reading/Writing/Waiting.
- **Ảnh 13: Grafana - Dashboard GYM MySQL**
  - Mở dashboard `GYM - MySQL`. Chụp biểu đồ MySQL Queries per second, Threads Connected, InnoDB Buffer Pool.

### 5. Minh chứng Thu thập Log Loki & Kịch bản Cảnh báo (Bước 5)
- **Ảnh 14: Grafana - Dashboard GYM Logs hoặc Explore Loki**
  - Vào Grafana -> Explore -> chọn Datasource `Loki`.
  - Nhập truy vấn: `{job="webapp"} |= "LOGIN_FAILED"` và bấm Run Query.
  - Chụp màn hình danh sách các dòng log đăng nhập sai hiển thị rõ ràng trên giao diện.
- **Ảnh 15: Grafana Alert Rule trạng thái Firing**
  - Vào mục Alerting -> Alert rules -> nhóm `GymSecurityAlerts`.
  - Xem quy tắc `GYM - Brute force login`. Chụp trạng thái cảnh báo màu đỏ `Firing`.
- **Ảnh 16: Log Webhook nhận được tại Alert-Sink**
  - Mở terminal chạy lệnh: `docker logs gym_alert_sink`
  - Chụp màn hình dòng chữ: `ALERT RECEIVED: {"receiver":"alert-sink-webhook","status":"firing"...}`.

### 6. Minh chứng Gia cố Bảo mật Toàn diện (Bước 6)
- **Ảnh 17: Terminal kiểm tra Mạng & UFW Firewall**
  - Chạy các lệnh sau trên terminal máy ảo và chụp kết quả:
    ```bash
    sudo ufw status verbose
    docker network ls
    docker exec -i gym_db mysql -uroot -pGymRootP@ssw0rd!2026 -e "SHOW GRANTS FOR 'gym_app'@'%';"
    ```
  - Thể hiện: UFW chỉ mở 80, 443, 2849; có 3 mạng `gym_frontend`, `gym_backend`, `gym_monitoring`; quyền `gym_app` chỉ có SELECT, INSERT, UPDATE, DELETE.

---

## PHẦN III: NỘI DUNG VĂN PHONG MẪU ĐỂ DÁN VÀO BÁO CÁO WORD/PDF

### Mục 1: Hiện thực Ứng dụng & CSDL Quản lý Phòng Gym
Hệ thống được thiết kế theo kiến trúc hướng đối tượng trên nền tảng PHP 8.2 kết hợp cơ sở dữ liệu MySQL 8.0 chuẩn InnoDB. Cơ sở dữ liệu gồm 7 thực thể liên kết chặt chẽ: Tài khoản người dùng (users), Hồ sơ hội viên (members), Gói dịch vụ (packages), Đội ngũ huấn luyện viên (trainers), Hợp đồng thẻ tập (memberships), Lịch biểu các bộ môn (schedules), và Đăng ký lớp tập (bookings). Mật khẩu được mã hóa an toàn bằng thuật toán bcrypt. Hệ thống phân chia 3 vai trò rõ rệt: Quản lý (toàn quyền quản trị và phân tích doanh thu), Huấn luyện viên (quản lý lịch dạy và điểm danh hội viên), và Hội viên (tra cứu gói tập và chủ động đặt chỗ theo thời gian thực).

### Mục 2: Cổng Đảo Chiều Nginx & Bảo Mật Vận Chuyển HTTPS
Cụm dịch vụ Nginx 1.27 được cấu hình làm cổng tiếp nhận tập trung (Reverse Proxy), chịu trách nhiệm định tuyến lưu lượng đến ứng dụng Web (`/`), công cụ quản trị CSDL (`/phpmyadmin/`) và hệ thống giám sát (`/grafana/`). Giao thức truyền thông được nâng cấp lên chuẩn bảo mật HTTPS sử dụng chứng chỉ số tự ký X.509 với trường Subject Alternative Name (SAN) cho địa chỉ IP máy chủ. Hệ thống thiết lập chính sách chuyển hướng bắt buộc toàn bộ lưu lượng HTTP (cổng 80) sang HTTPS (cổng 443) bằng mã phản hồi HTTP 301 Moved Permanently. Các tiêu đề bảo vệ cấp cao (HSTS, CSP, X-Frame-Options, X-Content-Type-Options) được triển khai triệt để nhằm ngăn chặn các nguy cơ tấn công Clickjacking, Cross-Site Scripting (XSS) và MIME Sniffing.

### Mục 3: Giám Sát Hiệu Năng & Thu Thập Nhật Ký Tập Trung
Hạ tầng giám sát hiệu năng sử dụng Prometheus để thu thập số liệu định kỳ từ cAdvisor (tài nguyên container), mysqld-exporter (thông số CSDL) và nginx-prometheus-exporter (thông lượng web). Số liệu được trực quan hóa trên Grafana thông qua 4 bảng điều khiển chuyên biệt. Đồng thời, hệ thống quản lý nhật ký tập trung sử dụng Grafana Loki kết hợp Promtail tự động bắt dòng xuất chuẩn (stdout/stderr) của các container, gắn thẻ phân loại (labeling) và lưu trữ phục vụ truy vấn LogQL tốc độ cao.

### Mục 4: Mô Phỏng Sự Cố An Ninh & Cảnh Báo Tự Động
Kịch bản sự cố mô phỏng cuộc tấn công dò quét mật khẩu (Brute Force) vào giao diện xác thực của người dùng quản trị. Khi số lần xác thực không thành công vượt quá ngưỡng 5 lần trong vòng 1 phút, quy tắc kiểm tra (Alert Rule) trong Grafana lập tức chuyển sang trạng thái cảnh báo (`FIRING`) và phát thông báo qua webhook tới phân hệ tiếp nhận sự cố (Alert-Sink). Đội ngũ vận hành nhanh chóng khoanh vùng địa chỉ IP nguồn, tài khoản mục tiêu và thời điểm phát sinh sự cố thông qua các truy vấn LogQL chuyên sâu, từ đó kích hoạt chính sách bảo vệ tài khoản và giới hạn tần suất yêu cầu (Rate Limiting).

### Mục 5: Gia Cố An Toàn Đa Tầng (Security Hardening)
Hệ thống áp dụng các tiêu chuẩn an ninh nghiêm ngặt:
- Phân vùng mạng 3 lớp (`frontend`, `backend` nội bộ có cờ `internal: true`, `monitoring`) đảm bảo CSDL MySQL được cách ly tuyệt đối khỏi Internet và Nginx.
- Phân quyền CSDL theo nguyên tắc đặc quyền tối thiểu (Least Privilege), tài khoản ứng dụng chỉ sở hữu các quyền thao tác dữ liệu cơ bản, loại bỏ hoàn toàn nguy cơ phá hủy cấu trúc dữ liệu.
- Giới hạn cứng tài nguyên CPU/RAM cho từng container, ngăn ngừa cạn kiệt tài nguyên hệ thống do tấn công từ chối dịch vụ.
- Tắt thông tin hiển thị phiên bản phần mềm (Apache, PHP, Nginx) nhằm ngăn ngừa kẻ tấn công thu thập thông tin khai thác lỗ hổng (Information Disclosure).
- Kích hoạt tường lửa UFW với chính sách mặc định chặn toàn bộ lưu lượng vào, chỉ cho phép cổng HTTPS (443), HTTP (80) và cổng SSH tùy biến an toàn (2849).