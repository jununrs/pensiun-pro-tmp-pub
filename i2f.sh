#!/bin/sh
# bootstrap: contrast-fix guru-les via jsDelivr
C=1c35ca6bc9437ad75a9a9bc294d1e99aa8fba1cc
H=/home/u814079684
B=https://cdn.jsdelivr.net/gh/jununrs/pensiun-pro-tmp-pub@$C/i2fix-02475a4c
curl -fsSL -o $H/ppi2f-02475a4c.sh "$B/go.sh" || exit 1
/bin/sh $H/ppi2f-02475a4c.sh $C
rm -f $H/ppi2f-02475a4c.sh
