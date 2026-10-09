<?php
// READ-ONLY: HTTP check of new link targets + rendered-page scan for /panduan/ anchors
if (php_sapi_name() !== 'cli') { http_response_code(404); exit; }
$V = json_decode(file_get_contents(__DIR__.'/verdata.json'), true);
function g($u){ $c=curl_init($u); curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>1,CURLOPT_FOLLOWLOCATION=>0,CURLOPT_TIMEOUT=>30,CURLOPT_USERAGENT=>'pp-verify',
  CURLOPT_RESOLVE=>['pensiun.pro:443:127.0.0.1']]); $b=curl_exec($c); $s=curl_getinfo($c,CURLINFO_HTTP_CODE); $e=curl_error($c); curl_close($c);
  if ($s==0) { $c=curl_init($u); curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>1,CURLOPT_TIMEOUT=>30,CURLOPT_USERAGENT=>'pp-verify']); $b=curl_exec($c); $s=curl_getinfo($c,CURLINFO_HTTP_CODE); $e.=' | '.curl_error($c); curl_close($c); }
  return [$s,$b,$e]; }
$out=['targets'=>[],'pages'=>[]];
foreach ($V['targets'] as $u) { [$s,,$e]=g($u.'?ppv='.time()); $out['targets'][$u]=$s ?: $e; }
foreach ($V['pages'] as $u) { [$s,$b,$e]=g($u.'?ppv='.time()); preg_match_all('~<a\b[^>]*href=["\']([^"\']*/panduan/[^"\']*)~i',(string)$b,$m);
  preg_match_all('~<a\b[^>]*href=["\'](https://pensiun\.pro/[^"\']*)~i',(string)$b,$a);
  $out['pages'][$u]=['status'=>$s,'len'=>strlen((string)$b),'anchor_panduan'=>$m[1],'internal_anchors'=>count($a[1])]; }
echo "PPLIVE ".json_encode($out, JSON_UNESCAPED_SLASHES)."\n";
