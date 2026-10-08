<?php
if (php_sapi_name() !== 'cli') { http_response_code(404); exit; }
$pub='/home/u814079684/domains/pensiun.pro/public_html/wp-content/uploads/ppbak7f3a9c21';
$n=0; $err=[];
if (is_dir($pub)) {
  foreach (scandir($pub) as $f) {
    if ($f==='.'||$f==='..') continue;
    $p="$pub/$f";
    if (is_file($p)) { if (!@unlink($p)) $err[]=$f; else $n++; }
  }
  if (!@rmdir($pub)) $err[]='rmdir';
}
echo "PPRM ".json_encode(['removed'=>$n,'dir_gone'=>!is_dir($pub),'err'=>$err], JSON_UNESCAPED_SLASHES)."\n";
