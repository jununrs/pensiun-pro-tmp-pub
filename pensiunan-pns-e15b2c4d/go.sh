#!/bin/sh
# one-shot publisher for pensiunan-pns posts; usage: sh go.sh <commit>
H=/home/u814079684
D=$H/pppns-e15b2c4d
M=$H/.pppns-e15b2c4d.log
B=https://raw.githubusercontent.com/jununrs/pensiun-pro-tmp-pub/$1/pensiunan-pns-e15b2c4d
if [ -e "$M" ]; then echo "already-ran"; cat "$M"; exit 0; fi
echo "start $(date -u +%FT%TZ)" > "$M"
rm -rf "$D"; mkdir -p "$D" && cd "$D" || exit 1
for i in 00 01 02 03 04 05 06 07 08 09; do
  curl -fsSL -o p$i.txt "$B/p$i.txt" || { echo "fetch fail $i"; cd $H; rm -rf "$D" "$M"; exit 1; }
done
cat p[0-9][0-9].txt | tr -d '\n\r ' | base64 -d > pkg.tgz 2>/dev/null
S=$(sha256sum pkg.tgz | cut -d' ' -f1)
if [ "$S" != "6c82d8fe1782ad098ebd7600aa5bc911ec646d040463a52189fe977faab74f2e" ]; then echo "sha fail $S"; cd $H; rm -rf "$D" "$M"; exit 1; fi
tar -xzf pkg.tgz || { echo "tar fail"; cd $H; rm -rf "$D" "$M"; exit 1; }
/usr/bin/php run.php >> "$M" 2>&1
cd $H && rm -rf "$D"
echo "dir_removed=$([ -d "$D" ] && echo no || echo yes)" >> "$M"
cat "$M"
