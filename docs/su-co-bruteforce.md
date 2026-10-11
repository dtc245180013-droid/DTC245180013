# BÁO CÁO PHÂN TÍCH VÀ XỬ LÝ SỰ CỐ AN NINH (INCIDENT POST-MORTEM)
## Sự cố: Tấn công dò mật khẩu (Brute Force) vào hệ thống Gym & Fitness

---

### 1. Thông tin chung về sự cố
- **Hệ thống bị ảnh hưởng:** Hệ thống Quản trị Phòng Gym & Thể thao (Gym & Fitness Management System).
- **Phân hệ mục tiêu:** Cổng xác thực đăng nhập HTTPS (`/login.php`).
- **Thời gian phát hiện:** 2026-10-11 13:04:45 (GMT+7).
- **Mức độ nghiêm trọng (Severity):** **Critical (Nghiêm trọng)**.
- **Kênh phát hiện:** Grafana Alerting kết hợp Grafana Loki & Promtail.

---

### 2. Mô tả diễn biến sự cố
Vào lúc 13:04:40 ngày 11/10/2026, hệ thống giám sát tập trung tự động kích hoạt cảnh báo `GYM - Brute force login` khi phát hiện một lượng lớn yêu cầu đăng nhập sai mật khẩu liên tiếp được gửi tới cổng `/login.php`.
Kẻ tấn công sử dụng công cụ tự động hóa gửi hơn 20 yêu cầu POST trong vòng chưa đầy 10 giây nhằm dò quét mật khẩu của tài khoản quản trị tối cao (`admin`).

---

### 3. Quy trình điều tra và bóc tách bằng LogQL
Đội ngũ DevOps / Security đã sử dụng cụm công cụ **Grafana Loki** để truy vấn nhật ký thời gian thực:

#### 3.1. Xác định đột biến tần suất đăng nhập thất bại
Sử dụng truy vấn LogQL Metric để đo lường:
```logql
sum(count_over_time({job="webapp"} |= "LOGIN_FAILED" [5m]))
```
- **Kết quả:** Đạt đỉnh **40 lần/5 phút**, vượt xa ngưỡng hoạt động thông thường (ngưỡng an toàn là dưới 3 lần/phút).

#### 3.2. Bóc tách địa chỉ IP và tài khoản nạn nhân
Sử dụng bộ lọc chuỗi LogQL:
```logql
{job="webapp"} |= "LOGIN_FAILED"
```
- **Mẫu nhật ký thu được:**
  ```text
  [2026-10-11 13:04:49] LOGIN_FAILED ip=172.22.0.5 user=admin reason=wrong_password
  [2026-10-11 13:04:48] LOGIN_FAILED ip=172.22.0.5 user=admin reason=wrong_password
  [2026-10-11 13:04:47] LOGIN_FAILED ip=172.22.0.5 user=admin reason=wrong_password
  ```
- **Nhận định:** Kẻ tấn công tập trung đánh vào tài khoản quản trị viên (`user=admin`), lý do thất bại hoàn toàn là `reason=wrong_password`.

#### 3.3. Kiểm tra tính sẵn sàng của hệ thống Nginx Reverse Proxy
```logql
{job="nginx"} |~ " (500|502|503|504) "
```
- **Kết quả:** Không có lỗi 5xx phát sinh từ luồng người dùng ứng dụng Gym; Nginx vẫn phản hồi HTTP 200/302 bình thường, chứng minh dịch vụ không bị nghẽn (DoS) bởi lượng request này.

---

### 4. Cơ chế cảnh báo tự động (Alerting Workflow)
1. **Thu thập (Scraping):** Promtail lắng nghe stdout/stderr của container `gym_web` qua Docker socket và chuyển tiếp về Loki với nhãn `{job="webapp"}`.
2. **Đánh giá (Evaluation):** Grafana Alert Scheduler định kỳ quét biểu thức:
   $$\text{count\_over\_time}(\{job=\text{"webapp"}\} \mid= \text{"LOGIN\_FAILED"}[1m]) > 5$$
3. **Phát lệnh (Triggering):** Khi số lần thất bại vượt quá 5 lần/phút, Alert Rule chuyển trạng thái sang `FIRING`.
4. **Thông báo (Notification):** Webhook được gửi trực tiếp qua giao thức HTTP POST đến dịch vụ `alert-sink:8080`, lưu lại toàn bộ payload an ninh phục vụ kiểm toán (audit).

---

### 5. Nguyên nhân gốc rễ (Root Cause)
- Trang đăng nhập ban đầu chưa cấu hình giới hạn tần suất yêu cầu (Rate Limiting) theo địa chỉ IP.
- Chưa có cơ chế khóa tạm thời (Account Lockout) sau 5 lần đăng nhập không thành công.
- Chưa tích hợp mã kiểm tra bảo mật (CAPTCHA).

---

### 6. Biện pháp khắc phục và phòng ngừa dài hạn (Remediation)
1. **Nginx Rate Limiting:** Cấu hình chỉ thị `limit_req_zone $binary_remote_addr zone=login_limit:10m rate=5r/m;` và áp dụng vào khối `location = /login.php`.
2. **Khóa tạm tài khoản (Brute-force lockout):** Bổ sung bảng theo dõi số lần đăng nhập thất bại trong cơ sở dữ liệu `gym.users`. Khóa tài khoản trong vòng 15 phút nếu nhập sai quá 5 lần.
3. **Bật xác thực đa yếu tố (2FA):** Bắt buộc áp dụng OTP qua email/ứng dụng đối với nhóm tài khoản có quyền Quản trị (Manager).
4. **Tích hợp Fail2Ban trên máy chủ VM:** Tự động chặn IP tại tầng tường lửa Linux (iptables/UFW) khi phát hiện cảnh báo từ Loki.

---
*Báo cáo được hoàn thiện và lưu trữ tại tài liệu dự án Gym & Fitness Management.*