<?php
if (php_sapi_name() !== 'cli') { http_response_code(404); exit; }
$_SERVER['HTTP_HOST']='pensiun.pro'; $_SERVER['SERVER_NAME']='pensiun.pro'; $_SERVER['REQUEST_URI']='/'; $_SERVER['HTTPS']='on'; $_SERVER['SERVER_PORT']=443;
@set_time_limit(600); @ini_set('memory_limit','512M');
require __DIR__.'/common.php';
require '/home/u814079684/domains/pensiun.pro/public_html/wp-load.php';
require_once ABSPATH.'wp-admin/includes/translation-install.php';
require_once ABSPATH.'wp-admin/includes/file.php';
kses_remove_filters();
global $wpdb;

$TOKEN = 'ppbak7f3a9c21';
$bdir = '/home/u814079684/pp-linkfix-backup-20261008';
$pubdir = ABSPATH . 'wp-content/uploads/' . $TOKEN;
@mkdir("$bdir/posts", 0700, true);
@mkdir("$bdir/meta", 0700, true);
@mkdir("$bdir/options", 0700, true);
@mkdir($pubdir, 0700, true);

$log = [
  'ok' => true,
  'started_at' => gmdate('c'),
  'site' => 'https://pensiun.pro',
  'before' => [
    'WPLANG' => get_option('WPLANG', '__unset__'),
    'locale' => get_locale(),
    'bloginfo_language' => get_bloginfo('language'),
    'available_languages' => get_available_languages(),
  ],
  'posts' => [],
  'postmeta' => [],
  'options' => [],
  'language' => [],
  'totals' => ['kebutuhan'=>0,'kontak'=>0,'posts_updated'=>0,'meta_updated'=>0,'options_updated'=>0],
];

$L1 = '%menghitung-kebutuhan-bulanan-pensiunan%';
$L2 = '%/kontak%'; // tighter than bare kontak to cut false positives; regex still exact

// ---- 1) posts/pages (published only) ----
$rows = $wpdb->get_results($wpdb->prepare(
  "SELECT ID,post_type,post_status,post_name,post_title,post_content,post_excerpt FROM {$wpdb->posts}
   WHERE post_status = 'publish' AND post_type IN ('post','page','wp_block','wp_template','wp_template_part','wp_navigation')
   AND (post_content LIKE %s OR post_content LIKE %s OR post_excerpt LIKE %s OR post_excerpt LIKE %s)",
  $L1, $L2, $L1, $L2
));
foreach ($rows as $r) {
  $c=[]; $m=[]; $newc = pplf_replace($r->post_content, $c, $m);
  $ce=[]; $me=[]; $newe = pplf_replace($r->post_excerpt, $ce, $me);
  if (!$c && !$ce) continue;
  $id = (int)$r->ID;
  $entry = ['id'=>$id,'type'=>$r->post_type,'status'=>$r->post_status,'slug'=>$r->post_name,'url'=>get_permalink($id),
    'sha0'=>hash('sha256',$r->post_content),'len0'=>strlen($r->post_content),'re'=>$c,'rex'=>$ce,'matches'=>array_values(array_unique(array_merge($m,$me)))];
  // backup
  $bf = "$bdir/posts/$id.html";
  if (!is_file($bf)) file_put_contents($bf, $r->post_content);
  if (hash_file('sha256', $bf) !== $entry['sha0']) { $entry['error']='backup sha mismatch'; $log['posts'][]=$entry; $log['ok']=false; continue; }
  file_put_contents("$bdir/posts/$id.excerpt.txt", $r->post_excerpt);
  // also stage for local pull (skip if > 900KB to keep fetchable)
  if (strlen($r->post_content) < 900000) {
    file_put_contents("$pubdir/$id.html", $r->post_content);
    if ($r->post_excerpt !== '') file_put_contents("$pubdir/$id.excerpt.txt", $r->post_excerpt);
  } else {
    // still store sha + first/last 2KB for integrity
    file_put_contents("$pubdir/$id.sha256", $entry['sha0']."\n".strlen($r->post_content)."\n");
    file_put_contents("$pubdir/$id.head.txt", substr($r->post_content,0,2048));
    file_put_contents("$pubdir/$id.tail.txt", substr($r->post_content,-2048));
    $entry['large']=true;
  }
  $upd = [];
  if ($c) $upd['post_content'] = $newc;
  if ($ce) $upd['post_excerpt'] = $newe;
  if ($wpdb->update($wpdb->posts, $upd, ['ID'=>$id]) === false) {
    $entry['error']='wpdb: '.$wpdb->last_error; $log['posts'][]=$entry; $log['ok']=false; continue;
  }
  clean_post_cache($id);
  do_action('litespeed_purge_post', $id);
  $p = get_post($id);
  $left = pplf_raw_hits($p->post_content);
  $entry += [
    'sha1'=>hash('sha256',$p->post_content),'len1'=>strlen($p->post_content),
    'content_match'=> ($c ? hash('sha256',$p->post_content)===hash('sha256',$newc) : true),
    'excerpt_match'=> ($ce ? hash('sha256',$p->post_excerpt)===hash('sha256',$newe) : true),
    'left_raw'=>$left,
    'title_unchanged'=>$p->post_title===$r->post_title,
    'slug_unchanged'=>$p->post_name===$r->post_name,
  ];
  if ($left['kebutuhan'] || $left['kontak']) { $entry['error']='left-over raw hits'; $log['ok']=false; }
  $log['totals']['kebutuhan'] += ($c['kebutuhan']??0)+($ce['kebutuhan']??0);
  $log['totals']['kontak'] += ($c['kontak']??0)+($ce['kontak']??0);
  $log['totals']['posts_updated']++;
  $log['posts'][] = $entry;
}

// ---- 2) postmeta (_menu_item_url and any other) ----
$pm = $wpdb->get_results($wpdb->prepare(
  "SELECT meta_id,post_id,meta_key,meta_value FROM {$wpdb->postmeta} WHERE meta_value LIKE %s OR meta_value LIKE %s", $L1, $L2
));
foreach ($pm as $r) {
  $c=[]; $m=[]; $new = pplf_replace($r->meta_value, $c, $m);
  if (!$c) continue;
  $mid=(int)$r->meta_id; $pid=(int)$r->post_id;
  $entry=['meta_id'=>$mid,'post_id'=>$pid,'key'=>$r->meta_key,'sha0'=>hash('sha256',$r->meta_value),'re'=>$c,'matches'=>array_values(array_unique($m)),'old'=>$r->meta_value,'new'=>$new];
  file_put_contents("$bdir/meta/$mid.txt", $r->meta_value);
  file_put_contents("$pubdir/meta-$mid.txt", $r->meta_value);
  if ($wpdb->update($wpdb->postmeta, ['meta_value'=>$new], ['meta_id'=>$mid]) === false) {
    $entry['error']='wpdb meta'; $log['postmeta'][]=$entry; $log['ok']=false; continue;
  }
  clean_post_cache($pid);
  $log['totals']['kebutuhan'] += ($c['kebutuhan']??0);
  $log['totals']['kontak'] += ($c['kontak']??0);
  $log['totals']['meta_updated']++;
  $log['postmeta'][]=$entry;
}

// ---- 3) options (widgets, theme_mods, etc.) — carefully with serialized ----
$op = $wpdb->get_results($wpdb->prepare(
  "SELECT option_id,option_name,option_value FROM {$wpdb->options} WHERE option_value LIKE %s OR option_value LIKE %s", $L1, $L2
));
foreach ($op as $r) {
  $c=[]; $m=[];
  // Try unserialize → walk → reserialize for widget/theme options
  $val = $r->option_value;
  $is_ser = is_serialized($val);
  $changed = false; $newval = $val;
  if ($is_ser) {
    $data = @unserialize($val);
    if ($data === false && $val !== 'b:0;') { /* fall through to raw */ $is_ser=false; }
    else {
      $walk = function(&$node) use (&$walk, &$c, &$m, &$changed) {
        if (is_string($node)) {
          $cc=[]; $mm=[]; $n = pplf_replace($node, $cc, $mm);
          if ($cc) { foreach ($cc as $k=>$v) $c[$k]=($c[$k]??0)+$v; $m=array_merge($m,$mm); $node=$n; $changed=true; }
        } elseif (is_array($node)) { foreach ($node as &$x) $walk($x); unset($x); }
        elseif (is_object($node)) { foreach ($node as $k=>&$x) $walk($x); unset($x); }
      };
      $walk($data);
      if ($changed) $newval = serialize($data);
    }
  }
  if (!$is_ser || !$changed) {
    $cc=[]; $mm=[]; $n = pplf_replace($val, $cc, $mm);
    if ($cc) { $c=$cc; $m=$mm; $newval=$n; $changed=true; }
  }
  if (!$changed) continue;
  $entry=['option_id'=>(int)$r->option_id,'name'=>$r->option_name,'serialized'=>$is_ser,'sha0'=>hash('sha256',$val),'re'=>$c,'matches'=>array_values(array_unique($m))];
  file_put_contents("$bdir/options/{$r->option_name}.txt", $val);
  file_put_contents("$pubdir/opt-{$r->option_name}.txt", $val);
  if ($wpdb->update($wpdb->options, ['option_value'=>$newval], ['option_id'=>(int)$r->option_id]) === false) {
    $entry['error']='wpdb option'; $log['options'][]=$entry; $log['ok']=false; continue;
  }
  wp_cache_delete($r->option_name, 'options');
  $log['totals']['kebutuhan'] += ($c['kebutuhan']??0);
  $log['totals']['kontak'] += ($c['kontak']??0);
  $log['totals']['options_updated']++;
  $log['options'][]=$entry;
}

// ---- 4) language ----
$lang = ['before_WPLANG'=>get_option('WPLANG','__unset__'),'before_locale'=>get_locale(),'available'=>get_available_languages()];
if (!in_array('id_ID', get_available_languages(), true)) {
  $dl = wp_download_language_pack('id_ID');
  $lang['download'] = $dl;
  if ($dl !== 'id_ID') { $lang['error']='download failed'; $log['ok']=false; }
}
$upd = update_option('WPLANG', 'id_ID');
$lang['update_option_result'] = $upd;
$lang['after_WPLANG'] = get_option('WPLANG');
// switch for this process verification
switch_to_locale('id_ID');
$lang['after_locale'] = get_locale();
$lang['after_bloginfo_language'] = get_bloginfo('language');
$lang['date_sample'] = date_i18n('j F Y', strtotime('2026-09-22'));
$lang['available_after'] = get_available_languages();
$log['language'] = $lang;

// ---- 5) leftover scan ----
$left_posts = $wpdb->get_results($wpdb->prepare(
  "SELECT ID,post_name,post_status,post_type FROM {$wpdb->posts}
   WHERE post_status='publish' AND (post_content LIKE %s OR post_content LIKE %s)", $L1, '%pensiun.pro/kontak%'
));
$leftover=[];
foreach ($left_posts as $r) {
  $content = $wpdb->get_var($wpdb->prepare("SELECT post_content FROM {$wpdb->posts} WHERE ID=%d",$r->ID));
  $c=[]; $m=[]; pplf_replace($content,$c,$m);
  if ($c) $leftover[]=['id'=>(int)$r->ID,'slug'=>$r->post_name,'would_still'=>$c];
}
$log['leftover_publish'] = $leftover;

// purge all LS cache
do_action('litespeed_purge_all');
$log['litespeed_purged'] = true;

// write log files
$log['finished_at'] = gmdate('c');
$log['backup_dir'] = $bdir;
$log['pub_backup_token'] = $TOKEN;
$log['pub_backup_files'] = array_values(array_map('basename', glob("$pubdir/*") ?: []));
file_put_contents("$bdir/log.json", json_encode($log, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
file_put_contents("$pubdir/log.json", json_encode($log, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
// deny listing
file_put_contents("$pubdir/.htaccess", "Require all denied\n");
file_put_contents("$pubdir/index.php", "<?php http_response_code(404); exit;\n");

echo "PPFIX ".json_encode($log, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
