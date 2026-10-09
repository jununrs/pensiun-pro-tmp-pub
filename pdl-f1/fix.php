<?php
// Remap <a href> values pointing to old /panduan/ URLs in published post_content. Only href values change.
if (php_sapi_name() !== 'cli') { http_response_code(404); exit; }
$_SERVER['HTTP_HOST']='pensiun.pro'; $_SERVER['SERVER_NAME']='pensiun.pro'; $_SERVER['REQUEST_URI']='/'; $_SERVER['HTTPS']='on'; $_SERVER['SERVER_PORT']=443;
@set_time_limit(600); @ini_set('memory_limit','512M');
require __DIR__.'/fixlib.php';
$D = json_decode(file_get_contents(__DIR__.'/fixdata.json'), true);
$map = $D['map'];
require '/home/u814079684/domains/pensiun.pro/public_html/wp-load.php';
kses_remove_filters();
global $wpdb;
$bdir = '/home/u814079684/pp-backup-20261009-panduan'; @mkdir($bdir, 0700, true);
$res = ['ok'=>true,'targets'=>[],'fixed'=>[],'skipped'=>[]];
// 1) every target must exist & be published
$tok = true;
foreach (array_unique(array_values($map)) as $u) {
  $path = trim(parse_url($u, PHP_URL_PATH), '/');
  if (strpos($path, 'category/') === 0) { $t = get_term_by('slug', substr($path, 9), 'category'); $ok = $t && !is_wp_error($t) && get_category_link($t) === $u; $res['targets'][$u] = $ok ? 'cat:'.$t->term_id.':'.$t->count : 'MISSING'; }
  else { $pid = url_to_postid($u); $p = $pid ? get_post($pid) : null; $ok = $p && $p->post_status === 'publish' && get_permalink($pid) === $u; $res['targets'][$u] = $ok ? 'post:'.$pid : 'MISSING'; }
  if (!$ok) $tok = false;
}
if (!$tok) { $res['ok'] = false; $res['abort'] = 'target missing'; echo "PPFIX ".json_encode($res, JSON_UNESCAPED_SLASHES)."\n"; exit; }
foreach ($D['posts'] as $id => $exp) {
  $id = (int)$id; $o = ['id'=>$id];
  try {
    $orig = $wpdb->get_var($wpdb->prepare("SELECT post_content FROM {$wpdb->posts} WHERE ID=%d AND post_status='publish'", $id));
    if ($orig === null) throw new Exception('missing/not published');
    $sha0 = hash('sha256', $orig);
    if (substr($sha0, 0, 16) !== $exp['sha16']) throw new Exception('content changed since dump');
    $new = ppl_rewrite($orig, $map, $hits);
    if (count($hits) !== (int)$exp['n']) throw new Exception('hit count '.count($hits).' != '.$exp['n']);
    if (ppl_neutral($orig) !== ppl_neutral($new)) throw new Exception('non-href diff');
    [$left, $ll] = ppl_anchor_panduan($new);
    if ($left) throw new Exception('left '.implode(',', $ll));
    file_put_contents("$bdir/$id.html", $orig);
    if (hash_file('sha256', "$bdir/$id.html") !== $sha0) throw new Exception('file backup mismatch');
    $rid = _wp_put_post_revision(get_post($id));
    if (!$rid || is_wp_error($rid)) throw new Exception('revision create failed');
    $rid = (int)$rid;
    if (hash('sha256', (string)$wpdb->get_var($wpdb->prepare("SELECT post_content FROM {$wpdb->posts} WHERE ID=%d", $rid))) !== $sha0) {
      if ($wpdb->update($wpdb->posts, ['post_content'=>$orig], ['ID'=>$rid], ['%s'], ['%d']) === false) throw new Exception('rev wpdb');
      clean_post_cache($rid); $o['rev_fixed'] = true;
      if (hash('sha256', (string)$wpdb->get_var($wpdb->prepare("SELECT post_content FROM {$wpdb->posts} WHERE ID=%d", $rid))) !== $sha0) throw new Exception('revision backup mismatch');
    }
    $p0 = get_post($id); $thumb0 = (int)get_post_thumbnail_id($id); $cats0 = wp_get_post_categories($id);
    if ($wpdb->update($wpdb->posts, ['post_content'=>$new], ['ID'=>$id], ['%s'], ['%d']) === false) throw new Exception('wpdb: '.$wpdb->last_error);
    clean_post_cache($id);
    do_action('litespeed_purge_post', $id);
    $p = get_post($id);
    $o += ['slug'=>$p->post_name,'n'=>count($hits),'rev'=>$rid,
      'content_match'=>hash('sha256', $p->post_content) === hash('sha256', $new),
      'unchanged_other'=>($p->post_title === $p0->post_title && $p->post_name === $p0->post_name && $p->post_excerpt === $p0->post_excerpt && $p->post_status === 'publish'
         && (int)get_post_thumbnail_id($id) === $thumb0 && wp_get_post_categories($id) === $cats0),
      'len0'=>strlen($orig),'len1'=>strlen($new),'sha0'=>substr($sha0,0,16),'sha1'=>substr(hash('sha256',$new),0,16)];
    if (!$o['content_match'] || !$o['unchanged_other']) $res['ok'] = false;
    $res['fixed'][] = $o;
  } catch (Throwable $e) { $o['error'] = $e->getMessage(); $res['skipped'][] = $o; $res['ok'] = false; }
}
do_action('litespeed_purge_all');
echo "PPFIX ".json_encode($res, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
// re-dump
$rows = $wpdb->get_results("SELECT ID,post_content FROM {$wpdb->posts} WHERE post_status='publish' AND post_type IN ('post','page') ORDER BY ID");
$z = ['posts'=>count($rows),'anchor_panduan'=>0,'raw_panduan'=>0,'raw_by_post'=>[]];
foreach ($rows as $r) { [$n, $l] = ppl_anchor_panduan($r->post_content); $z['anchor_panduan'] += $n; if ($n) echo "STILL|{$r->ID}|".implode(',', $l)."\n";
  $rc = substr_count($r->post_content, '/panduan/'); if ($rc) { $z['raw_panduan'] += $rc; $z['raw_by_post'][$r->ID] = $rc; } }
echo "PPVERIFY ".json_encode($z)."\n";
