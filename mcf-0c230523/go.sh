#!/bin/sh
# read-only diag for mobile-css-fix; usage: sh go.sh <commit>
H=/home/u814079684
D=$H/ppmcf-0c230523
M=$H/.ppmcf-0c230523.log
B=https://cdn.jsdelivr.net/gh/jununrs/pensiun-pro-tmp-pub@$1/mcf-0c230523
if [ -e "$M" ]; then echo "already-ran"; cat "$M"; exit 0; fi
echo "start $(date -u +%FT%TZ)" > "$M"
rm -rf "$D"; mkdir -p "$D" && cd "$D" || exit 1
curl -fsSL -o cssfix.php "$B/cssfix.php" && curl -fsSL -o diag.php "$B/diag.php" || { echo "fetch fail" >> "$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; }
[ "$(sha256sum cssfix.php | cut -d' ' -f1)" = "34b6df5c59ee2daea046383283d39cc5a866ae9ba0682ec46ebd3b3fd59bda93" ] && [ "$(sha256sum diag.php | cut -d' ' -f1)" = "ac3f4d144f8485221fdea19ee0df028ada386ab8eea89b921167f4bee99fa226" ] || { echo "sha fail" >> "$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; }
/usr/bin/php diag.php >> "$M" 2>&1
cd $H && rm -rf "$D"
echo "dir_removed=$([ -d "$D" ] && echo no || echo yes)" >> "$M"
cat "$M"
