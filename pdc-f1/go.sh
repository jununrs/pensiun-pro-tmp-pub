#!/bin/sh
H=/home/u814079684; D=$H/pp-pdc-f1; M=$H/.pp-pdc-f1.log
B=https://cdn.jsdelivr.net/gh/jununrs/pensiun-pro-tmp-pub@$1/pdc-f1
if [ -e "$M" ]; then echo "already-ran"; cat "$M"; exit 0; fi
echo "start $(date -u +%FT%TZ)" > "$M"
rm -rf "$D"; mkdir -p "$D" && cd "$D" || exit 1
curl -fsSL -o cfix.php "$B/cfix.php" && [ "$(sha256sum cfix.php | cut -d' ' -f1)" = "b5d0f75d0d74666b9135a951a442341964f6be41a74b799282bebae61e891941" ] || { echo "fetch/sha fail cfix.php" >> "$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; }
curl -fsSL -o cfixdata.json "$B/cfixdata.json" && [ "$(sha256sum cfixdata.json | cut -d' ' -f1)" = "a36b0848e71c68dfc6ffe7039b87a0ce66bd31f72686c9a6fe39798febeb3b21" ] || { echo "fetch/sha fail cfixdata.json" >> "$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; }
/usr/bin/php cfix.php >> "$M" 2>&1
cd $H && rm -rf "$D"
echo "dir_removed=$([ -d "$D" ] && echo no || echo yes) end $(date -u +%FT%TZ)" >> "$M"
cat "$M"
