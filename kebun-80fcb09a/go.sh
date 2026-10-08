#!/bin/sh
# one-shot publish berkebun-untuk-dijual (new post); usage: sh go.sh <commit>
H=/home/u814079684
D=$H/ppkb-80fcb09a
M=$H/.ppkb-80fcb09a.log
B=https://cdn.jsdelivr.net/gh/jununrs/pensiun-pro-tmp-pub@$1/kebun-80fcb09a
if [ -e "$M" ]; then echo "already-ran"; cat "$M"; exit 0; fi
echo "start $(date -u +%FT%TZ)" > "$M"
rm -rf "$D"; mkdir -p "$D" && cd "$D" || exit 1
for i in 00 01 02; do
  curl -fsSL -o p$i.txt "$B/p$i.txt" || { echo "fetch fail $i" >> "$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; }
done
cat p[0-9][0-9].txt | tr -d '\n\r ' | base64 -d > pkg.tgz 2>/dev/null
S=$(sha256sum pkg.tgz | cut -d' ' -f1)
if [ "$S" != "9ebf56038b611228c5bd0686c90fd44b9e8971418b906f4f18b582b9d3248bb2" ]; then echo "sha fail $S" >> "$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; fi
tar -xzf pkg.tgz || { echo "tar fail" >> "$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; }
/usr/bin/php run.php >> "$M" 2>&1
cd $H && rm -rf "$D"
echo "dir_removed=$([ -d "$D" ] && echo no || echo yes)" >> "$M"
cat "$M"
