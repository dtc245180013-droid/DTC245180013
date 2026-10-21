#!/bin/bash
for i in $(seq 1 12); do
    echo -n "Yeu cau dang nhap $i: "
    curl -k -s -o /dev/null -w "HTTP %{http_code}\n" -X POST https://localhost/login.php -d "username=admin&password=sai$i"
done