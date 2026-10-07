#!/bin/sh
# bootstrap: fetch+run ide-usaha-2 publisher via jsDelivr
C=1290268b0240c14e9263323005b9d1020e0188ec
H=/home/u814079684
B=https://cdn.jsdelivr.net/gh/jununrs/pensiun-pro-tmp-pub@$C/ide-usaha-2-308df80c
curl -fsSL -o $H/ppiu2-308df80c.sh "$B/go.sh" || exit 1
/bin/sh $H/ppiu2-308df80c.sh $C
rm -f $H/ppiu2-308df80c.sh
