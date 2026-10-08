<?php
// READ-ONLY diagnostic: per-post CSS scoping stats for posts using .pensiun-artikel
if (php_sapi_name() !== 'cli') { http_response_code(404); exit; }
$_SERVER['HTTP_HOST']='pensiun.pro'; $_SERVER['SERVER_NAME']='pensiun.pro'; $_SERVER['REQUEST_URI']='/'; $_SERVER['HTTPS']='on'; $_SERVER['SERVER_PORT']=443;
@set_time_limit(300); @ini_set('memory_limit','512M');
require __DIR__.'/cssfix.php';
require '/home/u814079684/domains/pensiun.pro/public_html/wp-load.php';
global $wpdb;
$all = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='post' AND post_status='publish'");
$rows = $wpdb->get_results("SELECT ID,post_name,post_type,post_status,post_modified,post_content FROM {$wpdb->posts} WHERE post_type IN ('post','page') AND post_status NOT IN ('trash','auto-draft','inherit') AND post_content LIKE '%pensiun-artikel%' ORDER BY ID");
$tot = ['posts'=>count($rows),'affected'=>0,'bad_at'=>0,'unpref'=>0]; $preludes = [];
echo "PPDIAG all_published_posts=$all wrapper_posts=".count($rows)."\n";
foreach ($rows as $r) {
  [$_, $s] = ppc_content($r->post_content, false);
  $un = $s['unpref_top'] + $s['unpref_nested'];
  $aff = ($s['bad_at'] + $un) > 0;
  if ($aff) $tot['affected']++;
  $tot['bad_at'] += $s['bad_at']; $tot['unpref'] += $un;
  foreach ($s['bad_preludes'] as $p) $preludes[$p] = ($preludes[$p] ?? 0) + 1;
  printf("P|%d|%s|%s|%s|st=%d|media=%d|ok=%d|bad=%d|top=%d|nest=%d|junk=%d|len=%d|sha=%s|mod=%s|%s\n",
    $r->ID, $r->post_name, $r->post_type, $r->post_status, $s['styles'], $s['media_total'], $s['media_ok'], $s['bad_at'],
    $s['unpref_top'], $s['unpref_nested'], $s['junk'], strlen($r->post_content), hash('sha256', $r->post_content), $r->post_modified,
    $aff ? implode(' ; ', array_slice($s['samples'], 0, 3)) : '');
}
echo "PRELUDES ".json_encode($preludes, JSON_UNESCAPED_SLASHES)."\n";
echo "PPTOTAL ".json_encode($tot)."\n";
