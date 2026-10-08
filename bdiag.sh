#!/bin/sh
# bootstrap: pensiun.pro link+lang diag via jsDelivr
C=6bdd5f6510f364ed1731270eb66268b956c50b99
H=/home/u814079684
B=https://cdn.jsdelivr.net/gh/jununrs/pensiun-pro-tmp-pub@$C/pplf-91c255bf
curl -fsSL -o $H/pppplf-91c255bf.sh "$B/go.sh" || exit 1
/bin/sh $H/pppplf-91c255bf.sh $C
rm -f $H/pppplf-91c255bf.sh
