#!/bin/sh
# one-shot: fetch publisher at pinned commit, run once, clean up
M=/home/u814079684/.ppbh.log
D=/home/u814079684/ppbh-f62d
B=https://raw.githubusercontent.com/jununrs/pensiun-pro-tmp-pub/143135d61b2e9c48c639e27f379545e8b3df193f/bagihasil-f62d6008bdfa
rm -f /home/u814079684/ppbh.sh
mkdir -p $D && cd $D || exit 1
for f in run.php d00.txt d01.txt d02.txt d03.txt d04.txt d05.txt d06.txt d07.txt; do
  curl -fsSL -o $f $B/$f || { cd /home/u814079684; rm -rf $D; echo "fetch fail $f"; exit 1; }
done
echo "start $(date -u +%FT%TZ)" > $M
/usr/bin/php -r 'echo "sapi=",PHP_SAPI," v=",PHP_VERSION,PHP_EOL;' >> $M 2>&1
/usr/bin/php $D/run.php >> $M 2>&1
cd /home/u814079684 && rm -rf $D
echo "dir_removed=$([ -d $D ] && echo no || echo yes)" >> $M
cat $M
