#!/bin/sh
H=/home/u814079684; D=$H/pp-pdl-f1; M=$H/.pp-pdl-f1.log
B=https://cdn.jsdelivr.net/gh/jununrs/pensiun-pro-tmp-pub@$1/pdl-f1
if [ -e "$M" ]; then echo "already-ran"; cat "$M"; exit 0; fi
echo "start $(date -u +%FT%TZ)" > "$M"
rm -rf "$D"; mkdir -p "$D" && cd "$D" || exit 1
curl -fsSL -o fix.php "$B/fix.php" && [ "$(sha256sum fix.php | cut -d' ' -f1)" = "29e7b6fde3aabf7f54c1fa7628563b05951583f530ce83d95e2a3723dd6f9b3d" ] || { echo "fetch/sha fail fix.php" >> "$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; }
curl -fsSL -o fixlib.php "$B/fixlib.php" && [ "$(sha256sum fixlib.php | cut -d' ' -f1)" = "8dc06f85ce7aa15e518b1fe304b53b4b73d6991da9c6e099f2a6497935f2a7c1" ] || { echo "fetch/sha fail fixlib.php" >> "$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; }
curl -fsSL -o fixdata.json "$B/fixdata.json" && [ "$(sha256sum fixdata.json | cut -d' ' -f1)" = "78b0178962edcae11093618d978f52a76c3cbbf3b139441d3cb6e4838dc25781" ] || { echo "fetch/sha fail fixdata.json" >> "$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; }
/usr/bin/php fix.php >> "$M" 2>&1
cd $H && rm -rf "$D"
echo "dir_removed=$([ -d "$D" ] && echo no || echo yes) end $(date -u +%FT%TZ)" >> "$M"
cat "$M"
