#!/bin/sh
# one-shot breadcrumb-fix update guru-les (post 303); usage: sh go.sh <commit>
H=/home/u814079684
D=$H/ppbcfix-2a813986
M=$H/.ppbcfix-2a813986.log
B=https://cdn.jsdelivr.net/gh/jununrs/pensiun-pro-tmp-pub@$1/bcfix-2a813986
if [ -e "$M" ]; then echo "already-ran"; cat "$M"; exit 0; fi
echo "start $(date -u +%FT%TZ)" > "$M"
rm -rf "$D"; mkdir -p "$D" && cd "$D" || exit 1
for i in 00 01 02 03 04 05 06 07 08 09 10; do
  curl -fsSL -o p$i.txt "$B/p$i.txt" || { echo "fetch fail $i" >> "$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; }
done
cat p[0-9][0-9].txt | tr -d '\n\r ' | base64 -d > pkg.tgz 2>/dev/null
S=$(sha256sum pkg.tgz | cut -d' ' -f1)
if [ "$S" != "2a8139868615a41d9ac72c31f4037261e85b71793323a04c42ecd03264ae6c75" ]; then echo "sha fail $S" >> "$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; fi
tar -xzf pkg.tgz || { echo "tar fail" >> "$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; }
/usr/bin/php run.php >> "$M" 2>&1
cd $H && rm -rf "$D"
echo "dir_removed=$([ -d "$D" ] && echo no || echo yes)" >> "$M"
cat "$M"
