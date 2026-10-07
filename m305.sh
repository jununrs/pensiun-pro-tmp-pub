#!/bin/sh
# bootstrap: mentor calc contrast-fix via jsDelivr
C=583165f1f242c9009349c88917a2959650a2a0de
H=/home/u814079684
B=https://cdn.jsdelivr.net/gh/jununrs/pensiun-pro-tmp-pub@$C/m305fix-af9a5d2b
curl -fsSL -o $H/ppm305-af9a5d2b.sh "$B/go.sh" || exit 1
/bin/sh $H/ppm305-af9a5d2b.sh $C
rm -f $H/ppm305-af9a5d2b.sh
