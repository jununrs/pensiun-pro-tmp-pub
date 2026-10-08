#!/bin/sh
# bootstrap: pensiun.pro link+lang apply via jsDelivr
C=cb54fb8dafe7136d492636c83c2c35ba8693ad92
H=/home/u814079684
B=https://cdn.jsdelivr.net/gh/jununrs/pensiun-pro-tmp-pub@$C/pplx-0bfd4166
curl -fsSL -o $H/pppplx-0bfd4166.sh "$B/go.sh" || exit 1
/bin/sh $H/pppplx-0bfd4166.sh $C
rm -f $H/pppplx-0bfd4166.sh
