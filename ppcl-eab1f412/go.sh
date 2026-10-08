#!/bin/sh
H=/home/u814079684
D=$H/ppppcl-eab1f412
M=$H/.ppppcl-eab1f412.log
B=https://cdn.jsdelivr.net/gh/jununrs/pensiun-pro-tmp-pub@$1/ppcl-eab1f412
if [ -e "$M" ]; then echo "already-ran"; cat "$M"; exit 0; fi
echo "start $(date -u +%FT%TZ)" > "$M"
rm -rf "$D"; mkdir -p "$D" && cd "$D" || exit 1
curl -fsSL -o cleanup.php "$B/cleanup.php" || { echo fetchfail >>"$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; }
[ "$(sha256sum cleanup.php | cut -d' ' -f1)" = "4a828877bb6374fc9662a279851c75ed5a2cba73169f991bd89772c698585886" ] || { echo shafail >>"$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; }
/usr/bin/php cleanup.php >>"$M" 2>&1
cd $H && rm -rf "$D"
echo "dir_removed=$([ -d "$D" ] && echo no || echo yes)" >>"$M"
cat "$M"
