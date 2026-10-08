<?php
// mobile-css-fix: repair wrongly-prefixed @media blocks in post_content of target posts only.
if (php_sapi_name() !== 'cli') { http_response_code(404); exit; }
$_SERVER['HTTP_HOST']='pensiun.pro'; $_SERVER['SERVER_NAME']='pensiun.pro'; $_SERVER['REQUEST_URI']='/'; $_SERVER['HTTPS']='on'; $_SERVER['SERVER_PORT']=443;
@set_time_limit(600); @ini_set('memory_limit','512M');
require __DIR__.'/cssfix.php';
$targets = json_decode(file_get_contents(__DIR__.'/targets.json'), true);
require '/home/u814079684/domains/pensiun.pro/public_html/wp-load.php';
global $wpdb;
$bdir = '/home/u814079684/pp-backup-20261008'; @mkdir($bdir, 0700, true);
$res = ['ok'=>true,'fixed'=>[],'skipped'=>[]];
foreach ($targets as $t) {
  $id = (int)$t['id']; $o = ['id'=>$id];
  try {
    $orig = $wpdb->get_var($wpdb->prepare("SELECT post_content FROM {$wpdb->posts} WHERE ID=%d", $id));
    if ($orig === null) throw new Exception('missing');
    $sha0 = hash('sha256', $orig);
    if ($sha0 !== $t['sha']) throw new Exception('content changed since diag sha='.substr($sha0,0,12));
    [$new, $st] = ppc_content($orig, true);
    [$_, $after] = ppc_content($new, false);
    if ($after['bad_at'] || $after['unpref_top'] || $after['unpref_nested'] || $after['junk']) throw new Exception('fix incomplete '.json_encode($after));
    if (ppc_strip_styles($orig) !== ppc_strip_styles($new)) throw new Exception('non-style content would change');
    if ($new === $orig) throw new Exception('no change');
    if (hash('sha256', $new) !== $t['sha_new']) throw new Exception('fixed content differs from locally verified build');
    // backups: file + post meta (verified)
    file_put_contents("$bdir/$id.html", $orig);
    if (hash_file('sha256', "$bdir/$id.html") !== $sha0) throw new Exception('file backup mismatch');
    // revision backup (separate posts row; not loaded on page views)
    $rid = _wp_put_post_revision(get_post($id));
    if (!$rid || is_wp_error($rid)) throw new Exception('revision backup failed');
    $rv = get_post($rid);
    if (!$rv || hash('sha256', $rv->post_content) !== $sha0) throw new Exception('revision backup mismatch');
    $p0 = get_post($id); $thumb0 = (int)get_post_thumbnail_id($id); $cats0 = wp_get_post_categories($id);
    $seo0 = get_post_meta($id, '_pensiun_pro_seo_title', true);
    if ($wpdb->update($wpdb->posts, ['post_content'=>$new], ['ID'=>$id], ['%s'], ['%d']) === false) throw new Exception('wpdb: '.$wpdb->last_error);
    clean_post_cache($id);
    do_action('litespeed_purge_post', $id);
    $p = get_post($id);
    $o += ['slug'=>$p->post_name,'url'=>get_permalink($id),'status'=>$p->post_status,
      'restored'=>$st['bad_at'],'reprefixed_selectors'=>$st['unpref_nested'] + $st['unpref_top'],'preludes'=>$st['bad_preludes'],
      'content_match'=>hash('sha256', $p->post_content) === hash('sha256', $new),
      'unchanged_other'=> ($p->post_title === $p0->post_title && $p->post_name === $p0->post_name && $p->post_excerpt === $p0->post_excerpt
         && (int)get_post_thumbnail_id($id) === $thumb0 && wp_get_post_categories($id) === $cats0 && get_post_meta($id,'_pensiun_pro_seo_title',true) === $seo0),
      'backup_revision'=>(int)$rid,'backup_file'=>"$bdir/$id.html",'len0'=>strlen($orig),'len1'=>strlen($new),'sha1'=>hash('sha256',$new)];
    $res['fixed'][] = $o;
  } catch (Throwable $e) { $o['error'] = $e->getMessage(); $res['skipped'][] = $o; $res['ok'] = false; }
}
if (function_exists('litespeed_purge_all')) {}
do_action('litespeed_purge_all');
echo "PPFIX ".json_encode($res, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
// post-fix full diag
$rows = $wpdb->get_results("SELECT ID,post_name,post_content FROM {$wpdb->posts} WHERE post_type IN ('post','page') AND post_status NOT IN ('trash','auto-draft','inherit') AND post_content LIKE '%pensiun-artikel%' ORDER BY ID");
$z = ['posts'=>count($rows),'affected'=>0,'bad_at'=>0,'unpref'=>0,'media_ok'=>0];
foreach ($rows as $r) { [$_, $s] = ppc_content($r->post_content, false);
  $u = $s['unpref_top'] + $s['unpref_nested']; if ($s['bad_at'] + $u) { $z['affected']++; echo "STILL|{$r->ID}|{$r->post_name}|bad={$s['bad_at']}|un=$u\n"; }
  $z['bad_at'] += $s['bad_at']; $z['unpref'] += $u; $z['media_ok'] += $s['media_ok']; }
echo "PPVERIFY ".json_encode($z)."\n";
