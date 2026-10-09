<?php
// READ-ONLY: contexts of every /panduan/ string in published posts, JSON-LD parse status, breadcrumbs, head canonical check
if (php_sapi_name() !== 'cli') { http_response_code(404); exit; }
$_SERVER['HTTP_HOST']='pensiun.pro'; $_SERVER['SERVER_NAME']='pensiun.pro'; $_SERVER['REQUEST_URI']='/'; $_SERVER['HTTPS']='on'; $_SERVER['SERVER_PORT']=443;
@set_time_limit(300); @ini_set('memory_limit','512M');
require '/home/u814079684/domains/pensiun.pro/public_html/wp-load.php';
global $wpdb;
$rows = $wpdb->get_results("SELECT ID,post_name,post_content FROM {$wpdb->posts} WHERE post_status='publish' AND post_type IN ('post','page') AND post_content LIKE '%/panduan/%' ORDER BY ID");
foreach ($rows as $r) {
  $c = $r->post_content; $id=(int)$r->ID;
  echo "P|$id|{$r->post_name}|".get_permalink($id)."|sha=".substr(hash('sha256',$c),0,16)."|len=".strlen($c)."|cats=".implode(',',wp_get_post_categories($id))."\n";
  // occurrences: url + 60 chars before (single-line)
  preg_match_all('~[^\s"\'<>]*/panduan/[^\s"\'<>]*~', $c, $m, PREG_OFFSET_CAPTURE);
  $seen=[];
  foreach ($m[0] as $x) { $pre = preg_replace('~\s+~',' ', substr($c, max(0,$x[1]-60), min(60,$x[1]))); $k=$x[0].'|'.$pre; $seen[$k]=($seen[$k]??0)+1; }
  foreach ($seen as $k=>$n) echo "O|$id|$n|$k\n";
  // ld+json blocks
  preg_match_all('~<script[^>]*type=["\']application/ld\+json["\'][^>]*>(.*?)</script>~is', $c, $L);
  foreach ($L[1] as $i=>$j) { $d=json_decode($j,true); $types=[];
    $walk=function($n) use (&$walk,&$types){ if(is_array($n)){ if(isset($n['@type'])) $types[]=is_array($n['@type'])?implode('+',$n['@type']):$n['@type']; foreach($n as $v) $walk($v);} };
    $walk($d);
    echo "J|$id|$i|ok=".($d!==null?1:0)."|err=".json_last_error_msg()."|len=".strlen($j)."|types=".implode(',',$types)."|panduan=".substr_count($j,'/panduan/')."\n";
    $bc=function($n) use (&$bc,$id){ if(!is_array($n)) return; if(($n['@type']??'')==='BreadcrumbList') echo "B|$id|".json_encode($n['itemListElement']??null, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n"; foreach($n as $v) $bc($v); };
    $bc($d);
  }
  $lc = preg_match_all('~<link[^>]*rel=["\']canonical~i',$c); $og = preg_match_all('~og:url~',$c); $hd = preg_match_all('~<head\b~i',$c); $sty=preg_match_all('~<script\b~i',$c);
  echo "K|$id|canon=$lc|og=$og|head_tag=$hd|scripts=$sty\n";
}
echo "PLUGINS ".json_encode(get_option('active_plugins'))." THEME ".get_stylesheet()."\n";
// head canonical check via loopback
foreach ([49,109,166] as $id) { $u=get_permalink($id);
  $ch=curl_init($u.'?ppv='.time()); curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>1,CURLOPT_TIMEOUT=>30,CURLOPT_RESOLVE=>['pensiun.pro:443:127.0.0.1']]); $b=(string)curl_exec($ch); curl_close($ch);
  $hp=stripos($b,'</head>');
  preg_match_all('~<link[^>]*rel=["\']canonical["\'][^>]*>~i',$b,$cm,PREG_OFFSET_CAPTURE);
  $o=[]; foreach($cm[0] as $x) $o[]=($x[1]<$hp?'HEAD':'BODY').' '.$x[0];
  preg_match_all('~<meta[^>]*og:url[^>]*>~i',$b,$om,PREG_OFFSET_CAPTURE); foreach($om[0] as $x) $o[]=($x[1]<$hp?'HEAD':'BODY').' '.$x[0];
  preg_match_all('~application/ld\+json~i',$b,$jm,PREG_OFFSET_CAPTURE); $jh=0;$jb=0; foreach($jm[0] as $x) { if($x[1]<$hp) $jh++; else $jb++; }
  echo "H|$id|len=".strlen($b)."|ldjson head=$jh body=$jb|".json_encode($o, JSON_UNESCAPED_SLASHES)."\n"; }
