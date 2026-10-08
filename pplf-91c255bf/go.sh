#!/bin/sh
# pensiun.pro link+lang diag; usage: sh go.sh <commit>
H=/home/u814079684
D=$H/pppplf-91c255bf
M=$H/.pppplf-91c255bf.log
B=https://cdn.jsdelivr.net/gh/jununrs/pensiun-pro-tmp-pub@$1/pplf-91c255bf
if [ -e "$M" ]; then echo "already-ran"; cat "$M"; exit 0; fi
echo "start $(date -u +%FT%TZ)" > "$M"
rm -rf "$D"; mkdir -p "$D" && cd "$D" || exit 1
for f in common.php diag.php; do curl -fsSL -o $f "$B/$f" || { echo "fetch fail $f" >> "$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; }; done
[ "$(sha256sum common.php | cut -d' ' -f1)" = "7ba39e1fb1fb1f49260a1c9c14391b3eca030dd899d6be478e018eaddfbc6c06" ] && [ "$(sha256sum diag.php | cut -d' ' -f1)" = "0609e3e266f93321fd0b2e71fe99fd4ca9708a3cc1fa2a8269ebe0a9c0da24c6" ] || { echo "sha fail" >> "$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; }
/usr/bin/php diag.php >> "$M" 2>&1
cd $H && rm -rf "$D"
echo "dir_removed=$([ -d "$D" ] && echo no || echo yes)" >> "$M"
cat "$M"
