#!/bin/sh
# bootstrap: fetch+run kebun publisher via jsDelivr
C=3c2b78bfd79373d15741c9424a8edb306a2231ae
H=/home/u814079684
B=https://cdn.jsdelivr.net/gh/jununrs/pensiun-pro-tmp-pub@$C/kebun-80fcb09a
curl -fsSL -o $H/ppkb-80fcb09a.sh "$B/go.sh" || exit 1
/bin/sh $H/ppkb-80fcb09a.sh $C
rm -f $H/ppkb-80fcb09a.sh
