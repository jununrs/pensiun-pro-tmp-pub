#!/bin/sh
H=/home/u814079684; D=$H/pp-pdl-a1; M=$H/.pp-pdl-a1.log
B=https://cdn.jsdelivr.net/gh/jununrs/pensiun-pro-tmp-pub@$1/pdl-a1
if [ -e "$M" ]; then echo "already-ran"; cat "$M"; exit 0; fi
echo "start $(date -u +%FT%TZ)" > "$M"
rm -rf "$D"; mkdir -p "$D" && cd "$D" || exit 1
curl -fsSL -o dump.php "$B/dump.php" && [ "$(sha256sum dump.php | cut -d' ' -f1)" = "0b9e3893c0debc9ef7f743761f7a820b5d59871b8ab2783d579101ab43581c15" ] || { echo "fetch/sha fail dump.php" >> "$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; }
/usr/bin/php dump.php >> "$M" 2>&1
cd $H && rm -rf "$D"
echo "dir_removed=$([ -d "$D" ] && echo no || echo yes) end $(date -u +%FT%TZ)" >> "$M"
cat "$M"
