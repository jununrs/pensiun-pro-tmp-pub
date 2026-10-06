#!/bin/sh
# one-shot restyle updater for posts 190/299/301; usage: sh go.sh <commit>
H=/home/u814079684
D=$H/pprst-5aff53ec
M=$H/.pprst-5aff53ec.log
B=https://raw.githubusercontent.com/jununrs/pensiun-pro-tmp-pub/$1/pprst-5aff53ec
if [ -e "$M" ]; then echo "already-ran"; cat "$M"; exit 0; fi
echo "start $(date -u +%FT%TZ)" > "$M"
rm -rf "$D"; mkdir -p "$D" && cd "$D" || exit 1
for i in 00 01; do
  curl -fsSL -o p$i.txt "$B/p$i.txt" || { echo "fetch fail $i" >> "$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; }
done
cat p[0-9][0-9].txt | tr -d '\n\r ' | base64 -d > pkg.tgz 2>/dev/null
S=$(sha256sum pkg.tgz | cut -d' ' -f1)
if [ "$S" != "91e1191814464fb2708370af17a410e050726eae7fa54c7678dd992c7b49d386" ]; then echo "sha fail $S" >> "$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; fi
tar -xzf pkg.tgz || { echo "tar fail" >> "$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; }
/usr/bin/php run.php >> "$M" 2>&1
cd $H && rm -rf "$D" $H/pprst-5aff53ec.sh
echo "dir_removed=$([ -d "$D" ] && echo no || echo yes)" >> "$M"
cat "$M"
