#!/bin/bash
set -e
DIR="/home/u814079684/domains/pensiun.pro/public_html/_gaji_pub"
mkdir -p "$DIR"
cd "$DIR"
php <<'PHP'
<?php
$b="";
for($i=0;$i<30;$i++){
  $u=sprintf("https://raw.githubusercontent.com/jununrs/pensiun-pro-tmp-pub/main/b64/part%02d.txt",$i);
  $c=@file_get_contents($u);
  if($c===false){fwrite(STDERR,"fail $i\n"); exit(1);}
  $b.=trim($c);
}
file_put_contents("pub.b64",$b);
$php=base64_decode($b);
file_put_contents("wp.php",$php);
$dbg=["b64"=>strlen($b),"php"=>strlen($php),"head"=>substr($php,0,60),"has_gz"=>function_exists("gzdecode"),"has_zlib"=>extension_loaded("zlib")];
if(preg_match('/\$PAYLOAD_GZ_B64 = <<<\'B64\'\r?\n(.*?)\r?\nB64;/s',$php,$m)){
  $raw=$m[1];
  $bin=base64_decode($raw,true);
  $gz=($bin!==false && function_exists("gzdecode")) ? @gzdecode($bin) : false;
  $posts=$gz!==false ? json_decode($gz,true) : null;
  $dbg["heredoc"]=strlen($raw);
  $dbg["bin"]=($bin===false?null:strlen($bin));
  $dbg["gz"]=($gz===false?null:strlen($gz));
  $dbg["posts"]=is_array($posts)?count($posts):null;
  $dbg["json_err"]=json_last_error_msg();
  $dbg["heredoc_head"]=substr($raw,0,32);
} else {
  $dbg["heredoc"]=null;
  $dbg["match_fail"]=true;
  $dbg["h4s_pos"]=strpos($php,"H4sI");
}
file_put_contents("debug.txt", json_encode($dbg, JSON_PRETTY_PRINT));
echo json_encode($dbg),"\n";
if(empty($dbg["posts"])){fwrite(STDERR,"payload invalid\n"); exit(2);}
$c=file_get_contents("wp.php");
$c=str_replace("@unlink(__FILE__);\n  exit;","file_put_contents(__DIR__.'/fail.txt','invalid'); exit;",$c);
file_put_contents("wp.php",$c);
PHP
php wp.php > result.json 2>&1 || true
echo DONE > done.txt
wc -c pub.b64 wp.php result.json debug.txt > status.txt 2>&1 || true
