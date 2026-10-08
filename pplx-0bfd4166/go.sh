#!/bin/sh
# pensiun.pro link+lang apply; usage: sh go.sh <commit>
H=/home/u814079684
D=$H/pppplx-0bfd4166
M=$H/.pppplx-0bfd4166.log
B=https://cdn.jsdelivr.net/gh/jununrs/pensiun-pro-tmp-pub@$1/pplx-0bfd4166
if [ -e "$M" ]; then echo "already-ran"; cat "$M"; exit 0; fi
echo "start $(date -u +%FT%TZ)" > "$M"
rm -rf "$D"; mkdir -p "$D" && cd "$D" || exit 1
for f in common.php fix.php; do curl -fsSL -o $f "$B/$f" || { echo "fetch fail $f" >> "$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; }; done
[ "$(sha256sum common.php | cut -d' ' -f1)" = "7ba39e1fb1fb1f49260a1c9c14391b3eca030dd899d6be478e018eaddfbc6c06" ] && [ "$(sha256sum fix.php | cut -d' ' -f1)" = "2e650879e0fceaa0e50f51af143c8236af412d8368729673171091db8e251015" ] || { echo "sha fail" >> "$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; }
/usr/bin/php fix.php >> "$M" 2>&1
cd $H && rm -rf "$D"
echo "dir_removed=$([ -d "$D" ] && echo no || echo yes)" >> "$M"
cat "$M"
