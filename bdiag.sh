#!/bin/sh
# bootstrap: pensiun.pro link+lang diag via jsDelivr
C=COMMIT_PLACEHOLDER
H=/home/u814079684
B=https://cdn.jsdelivr.net/gh/jununrs/pensiun-pro-tmp-pub@$C/pplf-91c255bf
curl -fsSL -o $H/pppplf-91c255bf.sh "$B/go.sh" || exit 1
/bin/sh $H/pppplf-91c255bf.sh $C
rm -f $H/pppplf-91c255bf.sh
