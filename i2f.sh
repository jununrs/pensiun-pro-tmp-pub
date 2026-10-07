#!/bin/sh
# bootstrap: contrast-fix guru-les via jsDelivr
C=3fed6ab2adee331d7801dbe9cb5903a7dd30f87a
H=/home/u814079684
B=https://cdn.jsdelivr.net/gh/jununrs/pensiun-pro-tmp-pub@$C/i2fix-02475a4c
curl -fsSL -o $H/ppi2f-02475a4c.sh "$B/go.sh" || exit 1
/bin/sh $H/ppi2f-02475a4c.sh $C
rm -f $H/ppi2f-02475a4c.sh
