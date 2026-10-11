#!/bin/bash
set -e
IP="192.168.233.128"
DIR="$(dirname "$0")/certs"
mkdir -p "$DIR"

openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout "$DIR/server.key" \
  -out "$DIR/server.crt" \
  -subj "/C=VN/ST=Hanoi/L=Hanoi/O=GymFitness/OU=IT/CN=$IP" \
  -addext "subjectAltName=IP:$IP,DNS:localhost"

chmod 600 "$DIR/server.key"
chmod 644 "$DIR/server.crt"
echo "Certificate generated for $IP and localhost"
