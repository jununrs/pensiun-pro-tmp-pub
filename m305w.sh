#!/bin/sh
# bootstrap: mentor TOC remove + full-width via jsDelivr
C=5ec66ae6d4afdc954d91ef1b076185eafea1edf2
H=/home/u814079684
B=https://cdn.jsdelivr.net/gh/jununrs/pensiun-pro-tmp-pub@$C/m305w-f597c701
curl -fsSL -o $H/ppm305w-f597c701.sh "$B/go.sh" || exit 1
/bin/sh $H/ppm305w-f597c701.sh $C
rm -f $H/ppm305w-f597c701.sh
