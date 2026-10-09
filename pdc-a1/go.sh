#!/bin/sh
H=/home/u814079684; D=$H/pp-pdc-a1; M=$H/.pp-pdc-a1.log
B=https://cdn.jsdelivr.net/gh/jununrs/pensiun-pro-tmp-pub@$1/pdc-a1
if [ -e "$M" ]; then echo "already-ran"; cat "$M"; exit 0; fi
echo "start $(date -u +%FT%TZ)" > "$M"
rm -rf "$D"; mkdir -p "$D" && cd "$D" || exit 1
curl -fsSL -o cdump.php "$B/cdump.php" && [ "$(sha256sum cdump.php | cut -d' ' -f1)" = "b877c4481c0da75763cb031f11e509e85e78d76011fac870d71c5a87bf487039" ] || { echo "fetch/sha fail cdump.php" >> "$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; }
/usr/bin/php cdump.php >> "$M" 2>&1
cd $H && rm -rf "$D"
echo "dir_removed=$([ -d "$D" ] && echo no || echo yes) end $(date -u +%FT%TZ)" >> "$M"
cat "$M"
