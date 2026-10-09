#!/bin/sh
H=/home/u814079684; D=$H/pp-pdl-v1; M=$H/.pp-pdl-v1.log
B=https://cdn.jsdelivr.net/gh/jununrs/pensiun-pro-tmp-pub@$1/pdl-v1
if [ -e "$M" ]; then echo "already-ran"; cat "$M"; exit 0; fi
echo "start $(date -u +%FT%TZ)" > "$M"
rm -rf "$D"; mkdir -p "$D" && cd "$D" || exit 1
curl -fsSL -o ver.php "$B/ver.php" && [ "$(sha256sum ver.php | cut -d' ' -f1)" = "d531f5f0ff06099e81ff07288ea3aa8a506a0519cfdab03a3bca0336a519a41d" ] || { echo "fetch/sha fail ver.php" >> "$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; }
curl -fsSL -o verdata.json "$B/verdata.json" && [ "$(sha256sum verdata.json | cut -d' ' -f1)" = "0e672ddc1658395c54249d6496064f9e46051fe02214276b69f369886d0cb6ab" ] || { echo "fetch/sha fail verdata.json" >> "$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; }
/usr/bin/php ver.php >> "$M" 2>&1
cd $H && rm -rf "$D"
echo "dir_removed=$([ -d "$D" ] && echo no || echo yes) end $(date -u +%FT%TZ)" >> "$M"
cat "$M"
