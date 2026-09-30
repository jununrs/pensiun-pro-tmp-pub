#!/bin/bash
set -e
DIR="/home/u814079684/domains/pensiun.pro/public_html/_gaji_pub"
mkdir -p "$DIR"
cd "$DIR"
php -r '
$b="";
for($i=0;$i<30;$i++){
  $u=sprintf("https://raw.githubusercontent.com/jununrs/pensiun-pro-tmp-pub/main/b64/part%02d.txt",$i);
  $c=@file_get_contents($u);
  if($c===false){fwrite(STDERR,"fail $i\n"); exit(1);}
  $b.=$c;
}
file_put_contents("pub.b64",$b);
file_put_contents("wp.php",base64_decode($b));
echo "ok ".strlen($b)." ".filesize("wp.php")."\n";
'
php wp.php > result.json 2>&1
echo DONE > done.txt
wc -c pub.b64 wp.php result.json > status.txt 2>&1 || true
