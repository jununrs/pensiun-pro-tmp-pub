<?php
// READ-ONLY: dump /panduan/ hrefs + other flagged hrefs in published post_content; list published posts & categories
if (php_sapi_name() !== 'cli') { http_response_code(404); exit; }
$_SERVER['HTTP_HOST']='pensiun.pro'; $_SERVER['SERVER_NAME']='pensiun.pro'; $_SERVER['REQUEST_URI']='/'; $_SERVER['HTTPS']='on'; $_SERVER['SERVER_PORT']=443;
@set_time_limit(300); @ini_set('memory_limit','512M');
require '/home/u814079684/domains/pensiun.pro/public_html/wp-load.php';
global $wpdb;
$rows = $wpdb->get_results("SELECT ID,post_type,post_name,post_title,post_content FROM {$wpdb->posts} WHERE post_status='publish' AND post_type IN ('post','page') ORDER BY ID");
$U = []; $O = []; $raw = []; $tot = ['posts'=>count($rows),'href_panduan'=>0,'posts_with'=>0];
foreach ($rows as $r) {
  $c = $r->post_content; $id = (int)$r->ID; $n = 0;
  preg_match_all('~<a\b[^>]*?\bhref\s*=\s*(["\'])(.*?)\1[^>]*>(.*?)</a>~is', $c, $m, PREG_SET_ORDER);
  foreach ($m as $x) {
    $h = $x[2]; $t = trim(preg_replace('~\s+~u',' ', wp_strip_all_tags($x[3])));
    if (preg_match('~^(?:https?://(?:www\.)?pensiun\.pro)?/panduan/~i', $h)) {
      $n++; $k = $h;
      $U[$k]['n'] = ($U[$k]['n'] ?? 0) + 1; $U[$k]['ids'][$id] = ($U[$k]['ids'][$id] ?? 0) + 1;
      if (count($U[$k]['a'] ?? []) < 4 && !in_array($t, $U[$k]['a'] ?? [], true)) $U[$k]['a'][] = mb_substr($t,0,80);
    } elseif (preg_match('~tentang-kami|kebijakan-privasi|bisnis\.pensiun\.pro~i', $h)) {
      $O[$h][$id] = ($O[$h][$id] ?? 0) + 1;
    }
  }
  $rc = substr_count($c, '/panduan/');
  if ($rc != $n) $raw[] = [$id, $rc, $n, array_slice(array_map(function($p) use ($c){return substr($c, max(0,$p-70), 160);}, ppd_pos($c,'/panduan/')),0,3)];
  if ($n) { $tot['posts_with']++; $tot['href_panduan'] += $n; }
  $cats = $r->post_type==='post' ? implode(',', wp_get_post_categories($id)) : '';
  echo "S|$id|{$r->post_type}|{$r->post_name}|$cats|{$r->post_title}|sha=".substr(hash('sha256',$c),0,16)."|n=$n\n";
}
function ppd_pos($s,$nd){$o=[];$p=0;while(($p=strpos($s,$nd,$p))!==false){$o[]=$p;$p++;}return $o;}
ksort($U);
foreach ($U as $h => $v) { $ids=[]; foreach ($v['ids'] as $i=>$c) $ids[]="$i:$c"; echo "U|$h|{$v['n']}|".implode(',',$ids)."|".implode(' ;; ',$v['a'])."\n"; }
foreach ($O as $h => $v) { $ids=[]; foreach ($v as $i=>$c) $ids[]="$i:$c"; echo "O|$h|".implode(',',$ids)."\n"; }
foreach (get_categories(['hide_empty'=>false]) as $t) echo "C|{$t->term_id}|{$t->slug}|{$t->name}|{$t->count}|".get_category_link($t)."\n";
echo "R ".json_encode($raw, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
// also non-content locations (report only)
$L='%/panduan/%';
echo "X ".json_encode(['meta'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID=pm.post_id WHERE p.post_status='publish' AND pm.meta_value LIKE %s",$L)),
 'options'=>$wpdb->get_col($wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_value LIKE %s",$L)),
 'excerpt'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status='publish' AND post_excerpt LIKE %s",$L)),
 'permalink'=>get_option('permalink_structure')])."\n";
echo "T ".json_encode($tot)."\n";
