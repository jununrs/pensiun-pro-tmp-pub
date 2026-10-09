<?php
// Fix non-link /panduan/ URL strings (in-body canonical, og:url, JSON-LD) + add missing BreadcrumbList item. URL strings only.
if (php_sapi_name() !== 'cli') { http_response_code(404); exit; }
$_SERVER['HTTP_HOST']='pensiun.pro'; $_SERVER['SERVER_NAME']='pensiun.pro'; $_SERVER['REQUEST_URI']='/'; $_SERVER['HTTPS']='on'; $_SERVER['SERVER_PORT']=443;
@set_time_limit(600); @ini_set('memory_limit','512M');
$D = json_decode(file_get_contents(__DIR__.'/cfixdata.json'), true);
$DRY = in_array('--dry', $argv ?? [], true);
if (!empty($LOCAL)) {} 
require '/home/u814079684/domains/pensiun.pro/public_html/wp-load.php';
kses_remove_filters();
global $wpdb;
function ldblocks($s){ preg_match_all('~<script[^>]*type=["\']application/ld\+json["\'][^>]*>(.*?)</script>~is',$s,$m); return $m[1]; }
function ld_ok($s,&$err){ foreach (ldblocks($s) as $j){ if (json_decode($j,true)===null){ $err=json_last_error_msg(); return false; } } return true; }
function bc_check($s){ $miss=0; $n=0; foreach (ldblocks($s) as $j){ $d=json_decode($j,true);
  $w=function($x) use (&$w,&$miss,&$n){ if(!is_array($x)) return; if(($x['@type']??'')==='BreadcrumbList') foreach(($x['itemListElement']??[]) as $li){ $n++; if(empty($li['item'])) $miss++; } foreach($x as $v) $w($v); }; $w($d);} return [$n,$miss]; }
function neutral($s){ return preg_replace('~https://pensiun\.pro/[^"\'\s<>]*~','U',$s); }
$bdir='/home/u814079684/pp-backup-20261009-canon'; if(!$DRY) @mkdir($bdir,0700,true);
$res=['ok'=>true,'dry'=>$DRY,'fixed'=>[],'skipped'=>[],'kinds'=>[]];
foreach ($D as $id=>$e) {
  $id=(int)$id; $o=['id'=>$id];
  try {
    $orig=$wpdb->get_var($wpdb->prepare("SELECT post_content FROM {$wpdb->posts} WHERE ID=%d AND post_status='publish'",$id));
    if ($orig===null) throw new Exception('missing');
    $sha0=hash('sha256',$orig); if (substr($sha0,0,16)!==$e['sha16']) throw new Exception('changed since dump');
    $perm=get_permalink($id); if ($perm!==$e['permalink']) throw new Exception('permalink mismatch '.$perm);
    $root=$e['root_new']; $self=$e['self_old'];
    $err=''; if (!ld_ok($orig,$err)) throw new Exception('ld invalid before: '.$err);
    $kinds=[]; $cnt=0;
    $new=preg_replace_callback('~https://pensiun\.pro/panduan/[^"\'\s<>]*~', function($m) use ($self,$perm,$root,$orig,&$kinds,&$cnt){
      $u=$m[0][0]; $pos=$m[0][1]; $pre=substr($orig,max(0,$pos-30),min(30,$pos));
      $kind = preg_match('~rel="canonical" href="$~',$pre)?'canonical':(preg_match('~og:url" content="$~',$pre)?'og_url':(preg_match('~"item": "$~',$pre)?'breadcrumb_item':(preg_match('~"@id": "$~',$pre)?'at_id':(preg_match('~"mainEntityOfPage": "$~',$pre)?'mainEntityOfPage':(preg_match('~"url": "$~',$pre)?'step_url':'other')))));
      $frag=''; $base=$u; if(($h=strpos($u,'#'))!==false){ $frag=substr($u,$h); $base=substr($u,0,$h); }
      if ($base===$self) { $k='self_'.$kind; $r=$perm.$frag; }
      elseif ($base==='https://pensiun.pro/panduan/') { $k='root_'.$kind; $r=$root.$frag; }
      else throw new Exception('unmapped '.$u);
      $kinds[$k]=($kinds[$k]??0)+1; $cnt++; return $r;
    }, $orig, -1, $c1, PREG_OFFSET_CAPTURE);
    if ($cnt!==(int)$e['n']) throw new Exception("count $cnt != {$e['n']}");
    // add missing item to last breadcrumb crumb (position 3, no item) inside ld+json
    $added=0;
    if ((int)$e['add_item']>0) {
      $new=preg_replace_callback('~(\{\s*"@type":\s*"ListItem",\s*"position":\s*3,\s*"name":\s*"(?:[^"\\\\]|\\\\.)*")(\s*\})~', function($m) use ($perm,&$added){ $added++; return $m[1].', "item": "'.$perm.'"'.$m[2]; }, $new);
      if ($added!==(int)$e['add_item']) throw new Exception("add_item $added != {$e['add_item']}");
      $kinds['breadcrumb_item_added']=$added;
    }
    // verification: only URL strings changed (+ the inserted item key)
    $no=neutral($orig); $a2=0;
    if ($added) $no=preg_replace_callback('~(\{\s*"@type":\s*"ListItem",\s*"position":\s*3,\s*"name":\s*"(?:[^"\\\\]|\\\\.)*")(\s*\})~', function($m) use (&$a2){ $a2++; return $m[1].', "item": "U"'.$m[2]; }, $no);
    if ($a2!==$added || $no!==neutral($new)) throw new Exception('non-URL diff');
    if (strpos($new,'/panduan/')!==false) throw new Exception('panduan left');
    if (!ld_ok($new,$err)) throw new Exception('ld invalid after: '.$err);
    [$bn,$bm]=bc_check($new); if ($bm) throw new Exception("breadcrumb missing item $bm");
    $o+=['slug'=>get_post_field('post_name',$id),'permalink'=>$perm,'replaced'=>$cnt,'kinds'=>$kinds,'crumbs'=>$bn,'len0'=>strlen($orig),'len1'=>strlen($new)];
    foreach($kinds as $k=>$v) $res['kinds'][$k]=($res['kinds'][$k]??0)+$v;
    if ($DRY) { $res['fixed'][]=$o; continue; }
    file_put_contents("$bdir/$id.html",$orig); if (hash_file('sha256',"$bdir/$id.html")!==$sha0) throw new Exception('backup mismatch');
    $rid=_wp_put_post_revision(get_post($id)); if(!$rid||is_wp_error($rid)) throw new Exception('rev fail'); $rid=(int)$rid;
    if (hash('sha256',(string)$wpdb->get_var($wpdb->prepare("SELECT post_content FROM {$wpdb->posts} WHERE ID=%d",$rid)))!==$sha0) {
      $wpdb->update($wpdb->posts,['post_content'=>$orig],['ID'=>$rid],['%s'],['%d']); clean_post_cache($rid); $o['rev_fixed']=true;
      if (hash('sha256',(string)$wpdb->get_var($wpdb->prepare("SELECT post_content FROM {$wpdb->posts} WHERE ID=%d",$rid)))!==$sha0) throw new Exception('rev mismatch'); }
    $p0=get_post($id); $th0=(int)get_post_thumbnail_id($id); $c0=wp_get_post_categories($id);
    if ($wpdb->update($wpdb->posts,['post_content'=>$new],['ID'=>$id],['%s'],['%d'])===false) throw new Exception('wpdb '.$wpdb->last_error);
    clean_post_cache($id); do_action('litespeed_purge_post',$id);
    $p=get_post($id);
    $o+=['rev'=>$rid,'content_match'=>hash('sha256',$p->post_content)===hash('sha256',$new),
      'unchanged_other'=>($p->post_title===$p0->post_title&&$p->post_name===$p0->post_name&&$p->post_excerpt===$p0->post_excerpt&&$p->post_status==='publish'&&(int)get_post_thumbnail_id($id)===$th0&&wp_get_post_categories($id)===$c0),
      'sha0'=>substr($sha0,0,16),'sha1'=>substr(hash('sha256',$new),0,16)];
    if(!$o['content_match']||!$o['unchanged_other']) $res['ok']=false;
    $res['fixed'][]=$o;
  } catch (Throwable $x) { $o['error']=$x->getMessage(); $res['skipped'][]=$o; $res['ok']=false; }
}
if (!$DRY) do_action('litespeed_purge_all');
echo "PPCFIX ".json_encode($res,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
// full rescan of published posts/pages
$rows=$wpdb->get_results("SELECT ID,post_content FROM {$wpdb->posts} WHERE post_status='publish' AND post_type IN ('post','page') ORDER BY ID");
$z=['posts'=>count($rows),'raw_panduan'=>0,'ld_blocks'=>0,'ld_invalid'=>[],'crumbs'=>0,'crumbs_missing_item'=>[]];
foreach($rows as $r){ $c=$r->post_content; $n=substr_count($c,'/panduan/'); if($n){$z['raw_panduan']+=$n; echo "STILL|{$r->ID}|$n\n";}
  foreach(ldblocks($c) as $j){ $z['ld_blocks']++; if(json_decode($j,true)===null) $z['ld_invalid'][]=(int)$r->ID; }
  [$bn,$bm]=bc_check($c); $z['crumbs']+=$bn; if($bm) $z['crumbs_missing_item'][$r->ID]=$bm; }
echo "PPCVERIFY ".json_encode($z)."\n";
// live head/body check
foreach ([49,109,166] as $id){ $u=get_permalink($id); $ch=curl_init($u.'?ppv='.time()); curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>1,CURLOPT_TIMEOUT=>30,CURLOPT_RESOLVE=>['pensiun.pro:443:127.0.0.1']]); $b=(string)curl_exec($ch); $st=curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
  $hp=stripos($b,'</head>'); preg_match_all('~<link[^>]*rel=["\']canonical["\'][^>]*>|<meta[^>]*og:url[^>]*>~i',$b,$cm,PREG_OFFSET_CAPTURE); $l=[]; foreach($cm[0] as $x) $l[]=($x[1]<$hp?'HEAD ':'BODY ').$x[0];
  preg_match_all('~<script[^>]*application/ld\+json[^>]*>(.*?)</script>~is',$b,$jm); $bad=0; foreach($jm[1] as $j) if(json_decode($j,true)===null) $bad++;
  echo "LIVE|$id|$st|panduan=".substr_count($b,'/panduan/')."|ld=".count($jm[1])."|ld_bad=$bad|".json_encode($l,JSON_UNESCAPED_SLASHES)."\n"; }
