#!/bin/sh
# one-shot replace 6 gaji featured images; usage: sh go.sh <commit>
H=/home/u814079684
D=$H/ppgf914742ab
M=$H/.ppgf914742ab.log
B=https://cdn.jsdelivr.net/gh/jununrs/pensiun-pro-tmp-pub@$1/gfv2-914742ab
if [ -e "$M" ]; then echo "already-ran"; cat "$M"; exit 0; fi
echo "start $(date -u +%FT%TZ)" > "$M"
rm -rf "$D"; mkdir -p "$D" && cd "$D" || exit 1
for i in 00 01 02 03 04 05 06 07 08 09 10 11 12 13 14 15 16 17 18 19 ; do
  curl -fsSL -o p$i.txt "$B/p$i.txt" || { echo "fetch fail $i" >> "$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; }
done
cat p[0-9][0-9].txt | tr -d '\n\r ' | base64 -d > pkg.tgz 2>/dev/null
S=$(sha256sum pkg.tgz | cut -d' ' -f1)
if [ "$S" != "914742ab68ee1f7f13a8ff8a925735a292c853200868a0610f3bf8d4ee66bba5" ]; then echo "sha fail $S" >> "$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; fi
tar -xzf pkg.tgz || { echo "tar fail" >> "$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; }
/usr/bin/php run.php >> "$M" 2>&1
cd $H && rm -rf "$D"
echo "dir_removed=$([ -d "$D" ] && echo no || echo yes)" >> "$M"
cat "$M"
