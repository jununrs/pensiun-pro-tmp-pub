#!/bin/sh
# mobile-css-fix apply (32 posts); usage: sh go.sh <commit>
H=/home/u814079684
D=$H/ppmcfx-8b40c4e2
M=$H/.ppmcfx-8b40c4e2.log
B=https://cdn.jsdelivr.net/gh/jununrs/pensiun-pro-tmp-pub@$1/mcfx-8b40c4e2
if [ -e "$M" ]; then echo "already-ran"; cat "$M"; exit 0; fi
echo "start $(date -u +%FT%TZ)" > "$M"
rm -rf "$D"; mkdir -p "$D" && cd "$D" || exit 1
for f in cssfix.php fix.php targets.json; do curl -fsSL -o $f "$B/$f" || { echo "fetch fail $f" >> "$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; }; done
[ "$(sha256sum cssfix.php | cut -d' ' -f1)" = "34b6df5c59ee2daea046383283d39cc5a866ae9ba0682ec46ebd3b3fd59bda93" ] && [ "$(sha256sum fix.php | cut -d' ' -f1)" = "dba70d11625c6d462dae08c1af57d2882a2ada28a58a80aaecb957bb7452c523" ] && [ "$(sha256sum targets.json | cut -d' ' -f1)" = "a10f644e5ad516c2d1c37991a329d9ca7e8017fa79c5b91b3bd816a05ad419f4" ] || { echo "sha fail" >> "$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; }
/usr/bin/php fix.php >> "$M" 2>&1
cd $H && rm -rf "$D"
echo "dir_removed=$([ -d "$D" ] && echo no || echo yes)" >> "$M"
cat "$M"
