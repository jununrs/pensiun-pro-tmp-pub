#!/bin/sh
# bootstrap: breadcrumb-fix guru-les post 303 via jsDelivr
C=d89590998284e964b79cee976d0928cc0c1789be
H=/home/u814079684
B=https://cdn.jsdelivr.net/gh/jununrs/pensiun-pro-tmp-pub@$C/bcfix-2a813986
curl -fsSL -o $H/ppbcfix-2a813986.sh "$B/go.sh" || exit 1
/bin/sh $H/ppbcfix-2a813986.sh $C
rm -f $H/ppbcfix-2a813986.sh
