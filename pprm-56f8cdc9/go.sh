#!/bin/sh
H=/home/u814079684
D=$H/pppprm-56f8cdc9
M=$H/.pppprm-56f8cdc9.log
B=https://cdn.jsdelivr.net/gh/jununrs/pensiun-pro-tmp-pub@$1/pprm-56f8cdc9
if [ -e "$M" ]; then echo "already-ran"; cat "$M"; exit 0; fi
echo "start $(date -u +%FT%TZ)" > "$M"
rm -rf "$D"; mkdir -p "$D" && cd "$D" || exit 1
curl -fsSL -o rmstage.php "$B/rmstage.php" || { echo fetchfail >>"$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; }
[ "$(sha256sum rmstage.php | cut -d' ' -f1)" = "0d774634833d2fb1060c8dfe3307a75e11f456c6aa0ccbb1a6b0e16aa9b00bd8" ] || { echo shafail >>"$M"; cd $H; rm -rf "$D"; cat "$M"; exit 1; }
/usr/bin/php rmstage.php >>"$M" 2>&1
cd $H && rm -rf "$D"
echo "dir_removed=$([ -d "$D" ] && echo no || echo yes)" >>"$M"
cat "$M"
