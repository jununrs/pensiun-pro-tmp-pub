<?php
$L="/home/u814079684/domains/pensiun.pro/public_html/_pprst.log";
$B="https://raw.githubusercontent.com/jununrs/pensiun-pro-tmp-pub/main/pprst-5aff53ec/";
file_put_contents($L,"boot\n");
$s="";
foreach(["00","01"] as $i){
  $c=@file_get_contents($B."p$i.txt");
  if($c===false){file_put_contents($L,"fail$i\n",FILE_APPEND); exit(1);}
  $s.=preg_replace('/\s+/','',$c);
}
$bin=base64_decode($s);
file_put_contents("/home/u814079684/pkg.tgz",$bin);
file_put_contents($L,"pkg ".strlen($bin)."\n",FILE_APPEND);
$D="/home/u814079684/pprst-d";
@mkdir($D); chdir($D);
passthru("tar -xzf /home/u814079684/pkg.tgz 2>&1",$e);
file_put_contents($L,"tar $e\n",FILE_APPEND);
passthru("/usr/bin/php run.php 2>&1",$e2);
file_put_contents($L,"php $e2\n",FILE_APPEND);
chdir("/home/u814079684");
passthru("rm -rf $D /home/u814079684/pkg.tgz /home/u814079684/t.php");
file_put_contents($L,"done\n",FILE_APPEND);
