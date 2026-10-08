<?php
if (php_sapi_name() !== 'cli') { http_response_code(404); exit; }
$TOKEN='ppbak7f3a9c21';
$bdir='/home/u814079684/pp-linkfix-backup-20261008';
$pub= '/home/u814079684/domains/pensiun.pro/public_html/wp-content/uploads/'.$TOKEN;
$tgz="$bdir/posts-backup.tgz";
$res=['ok'=>false];
try {
  if (!is_dir("$bdir/posts")) throw new Exception('no backup dir');
  // create tarball of originals
  $cmd = 'tar -C '.escapeshellarg($bdir).' -czf '.escapeshellarg($tgz).' posts meta options log.json 2>&1';
  exec($cmd, $out, $ec);
  if ($ec!==0 || !is_file($tgz)) throw new Exception('tar fail '.implode(' ',$out));
  $sha=hash_file('sha256',$tgz); $sz=filesize($tgz);
  // base64 chunks into pub
  $b64=base64_encode(file_get_contents($tgz));
  $chunk=40000; $n=0; $files=[];
  for ($i=0; $i<strlen($b64); $i+=$chunk) {
    $fn=sprintf('c%02d.txt',$n++);
    file_put_contents("$pub/$fn", substr($b64,$i,$chunk));
    $files[]=$fn;
  }
  file_put_contents("$pub/META.txt", json_encode(['sha256'=>$sha,'size'=>$sz,'chunks'=>$n,'b64_len'=>strlen($b64)], JSON_UNESCAPED_SLASHES));
  // remove original html from pub (keep chunks + META + deny)
  foreach (glob("$pub/*.html") ?: [] as $f) @unlink($f);
  foreach (glob("$pub/*.excerpt.txt") ?: [] as $f) @unlink($f);
  @unlink("$pub/log.json");
  file_put_contents("$pub/.htaccess", "Require all denied\n");
  file_put_contents("$pub/index.php", "<?php http_response_code(404); exit;\n");
  $res=['ok'=>true,'sha256'=>$sha,'size'=>$sz,'chunks'=>$n,'files'=>$files,'pub_left'=>array_values(array_map('basename',glob("$pub/*")?:[])),'server_tgz'=>$tgz];
} catch (Throwable $e) { $res['error']=$e->getMessage(); }
echo "PPCLEAN ".json_encode($res, JSON_UNESCAPED_SLASHES)."\n";
