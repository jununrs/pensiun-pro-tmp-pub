#!/bin/sh
H=/home/u814079684
D=$H/pprst-5aff53ec
PH=$H/domains/pensiun.pro/public_html
M=$PH/_pprst.log
B=https://raw.githubusercontent.com/jununrs/pensiun-pro-tmp-pub/$1/pprst-5aff53ec
echo start > "$M"
rm -rf "$D"; mkdir -p "$D" && cd "$D" || { echo mkdirfail >> "$M"; exit 1; }
for i in 00 01; do
  wget -qO p$i.txt "$B/p$i.txt" >> "$M" 2>&1 || php -r "file_put_contents('p$i.txt', file_get_contents('$B/p$i.txt'));" || { echo fetchfail$i >> "$M"; exit 1; }
done
echo fetched >> "$M"
cat p[0-9][0-9].txt | tr -d '\n\r ' | base64 -d > pkg.tgz 2>>"$M"
S=$(sha256sum pkg.tgz | cut -d' ' -f1)
echo sha=$S >> "$M"
if [ "$S" != "91e1191814464fb2708370af17a410e050726eae7fa54c7678dd992c7b49d386" ]; then echo shafail >> "$M"; exit 1; fi
tar -xzf pkg.tgz >> "$M" 2>&1 || { echo tarfail >> "$M"; exit 1; }
/usr/bin/php run.php >> "$M" 2>&1
echo php_done >> "$M"
cd $H && rm -rf "$D" $H/r.sh $H/t.php
echo done >> "$M"
cat "$M"
