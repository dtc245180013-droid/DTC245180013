#!/bin/bash
set -e
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$DIR"
mkdir -p certs
cat << 'EOF' > certs/openssl.cnf
[req]
default_bits = 2048
prompt = no
default_md = sha256
distinguished_name = dn
x509_extensions = v3_req

[dn]
C = VN
ST = Thai Nguyen
L = Thai Nguyen
O = ICTU
OU = CNTT_K23C
CN = 192.168.233.128

[v3_req]
basicConstraints = CA:FALSE
keyUsage = nonRepudiation, digitalSignature, keyEncipherment
subjectAltName = @alt_names

[alt_names]
IP.1 = 192.168.233.128
IP.2 = 127.0.0.1
DNS.1 = localhost
EOF

openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout certs/server.key \
  -out certs/server.crt \
  -config certs/openssl.cnf -extensions v3_req

chmod 600 certs/server.key
chmod 644 certs/server.crt
rm -f certs/openssl.cnf
echo "Generated SSL certificate for 192.168.233.128 successfully"