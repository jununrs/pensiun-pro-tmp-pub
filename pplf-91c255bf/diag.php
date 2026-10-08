<?php
if (php_sapi_name() !== 'cli') { http_response_code(404); exit; }
$_SERVER['HTTP_HOST']='pensiun.pro'; $_SERVER['SERVER_NAME']='pensiun.pro'; $_SERVER['REQUEST_URI']='/'; $_SERVER['HTTPS']='on'; $_SERVER['SERVER_PORT']=443;
@set_time_limit(600); @ini_set('memory_limit','512M');
require __DIR__.'/common.php';
require '/home/u814079684/domains/pensiun.pro/public_html/wp-load.php';
require_once ABSPATH.'wp-admin/includes/translation-install.php';
require_once ABSPATH.'wp-admin/includes/file.php';
global $wpdb;
$L1 = '%menghitung-kebutuhan-bulanan-pensiunan%'; $L2 = '%kontak%';
$o = ['wp'=>get_bloginfo('version'),'locale'=>get_locale(),'WPLANG_opt'=>get_option('WPLANG', '__unset__'),'WPLANG_const'=>defined('WPLANG')?WPLANG:null,
  'available_languages'=>get_available_languages(),'lang_dir'=>WP_LANG_DIR,'lang_dir_exists'=>is_dir(WP_LANG_DIR),'lang_dir_writable'=>wp_is_writable(is_dir(WP_LANG_DIR)?WP_LANG_DIR:WP_CONTENT_DIR),
  'fs_method'=>get_filesystem_method(),'can_install_lang'=>wp_can_install_language_pack(),'bloginfo_language'=>get_bloginfo('language'),
  'theme'=>['name'=>wp_get_theme()->get('Name'),'stylesheet'=>get_stylesheet(),'template'=>get_template(),'version'=>wp_get_theme()->get('Version')],
  'active_plugins'=>get_option('active_plugins'),'litespeed'=>defined('LSCWP_V')?LSCWP_V:null];
$tr = translations_api('core'); $o['id_ID_available'] = null;
if (!is_wp_error($tr)) foreach ($tr['translations'] as $t) if ($t['language']==='id_ID') $o['id_ID_available'] = ['version'=>$t['version'],'updated'=>$t['updated']];
if (is_wp_error($tr)) $o['translations_api_error'] = $tr->get_error_message();
// theme grep
$tg = [];
foreach ([get_template_directory(), get_stylesheet_directory()] as $root) {
  $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
  foreach ($it as $f) { if (!preg_match('/\.(php|html|json)$/', $f)) continue; $lines = @file($f); if (!$lines) continue;
    foreach ($lines as $n => $ln) if (preg_match('/en_US|en-US|og:locale|language_attributes|<html|kontak|menghitung-kebutuhan|\bWPLANG\b|get_locale|setlocale|date_i18n|wp_date|get_the_date/i', $ln))
      $tg[] = substr(str_replace($root, '', $f), 0, 80).':'.($n+1).': '.substr(trim($ln), 0, 220); }
}
$o['theme_grep'] = array_values(array_unique($tg));
echo "PPDIAG ".json_encode($o, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
// posts
$rows = $wpdb->get_results($wpdb->prepare("SELECT ID,post_type,post_status,post_name,post_title,post_content,post_excerpt FROM {$wpdb->posts} WHERE (post_content LIKE %s OR post_content LIKE %s OR post_excerpt LIKE %s OR post_excerpt LIKE %s)", $L1, $L2, $L1, $L2));
$bdir = '/home/u814079684/pp-linkfix-backup-20261008'; @mkdir("$bdir/posts", 0700, true);
$targets = [];
foreach ($rows as $r) {
  $raw = pplf_raw_hits($r->post_content); $rawx = pplf_raw_hits($r->post_excerpt);
  $c = []; $m = []; $new = pplf_replace($r->post_content, $c, $m);
  $ce = []; $me = []; pplf_replace($r->post_excerpt, $ce, $me);
  $left = pplf_raw_hits($new);
  $line = ['id'=>(int)$r->ID,'type'=>$r->post_type,'status'=>$r->post_status,'slug'=>$r->post_name,'len'=>strlen($r->post_content),'sha'=>hash('sha256',$r->post_content),
    'raw'=>$raw,'rawx'=>$rawx,'re'=>$c,'rex'=>$ce,'matches'=>array_values(array_unique($m)),'left_raw'=>$left];
  if ($left['kebutuhan'] || $left['kontak']) $line['left_ctx'] = array_slice(array_merge(pplf_ctx($new, '~menghitung-kebutuhan-bulanan-pensiunan~'), pplf_ctx($new, '~kontak(?:\\\\?/|["\'#?])~i')), 0, 6);
  if ($r->post_type !== 'revision') echo "P ".json_encode($line, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
  else $targets['revisions_with_hits'][] = (int)$r->ID;
  if ($r->post_type !== 'revision' && $c) { file_put_contents("$bdir/posts/{$r->ID}.html", $r->post_content); }
}
echo "REVS ".json_encode($targets['revisions_with_hits'] ?? [])."\n";
// postmeta
$pm = $wpdb->get_results($wpdb->prepare("SELECT meta_id,post_id,meta_key,meta_value FROM {$wpdb->postmeta} WHERE meta_value LIKE %s OR meta_value LIKE %s", $L1, $L2));
foreach ($pm as $r) { $c=[];$m=[]; pplf_replace($r->meta_value,$c,$m); $p = get_post($r->post_id);
  echo "M ".json_encode(['meta_id'=>(int)$r->meta_id,'post_id'=>(int)$r->post_id,'ptype'=>$p?$p->post_type:null,'pstatus'=>$p?$p->post_status:null,'key'=>$r->meta_key,'len'=>strlen($r->meta_value),'raw'=>pplf_raw_hits($r->meta_value),'re'=>$c,'matches'=>array_values(array_unique($m)),'val'=>strlen($r->meta_value)<400?$r->meta_value:substr($r->meta_value,0,200)], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n"; }
// options
$op = $wpdb->get_results($wpdb->prepare("SELECT option_name,autoload,LENGTH(option_value) l,option_value FROM {$wpdb->options} WHERE option_value LIKE %s OR option_value LIKE %s", $L1, $L2));
foreach ($op as $r) { $c=[];$m=[]; pplf_replace($r->option_value,$c,$m);
  echo "O ".json_encode(['name'=>$r->option_name,'len'=>(int)$r->l,'serialized'=>is_serialized($r->option_value),'raw'=>pplf_raw_hits($r->option_value),'re'=>$c,'matches'=>array_values(array_unique($m)),'ctx'=>array_slice(pplf_ctx($r->option_value,'~menghitung-kebutuhan-bulanan-pensiunan|kontak~i',50),0,4)], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n"; }
// term/user meta & comments just counts
echo "X ".json_encode(['termmeta'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->termmeta} WHERE meta_value LIKE %s OR meta_value LIKE %s",$L1,$L2)),
  'term_desc'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE description LIKE %s OR description LIKE %s",$L1,$L2)),
  'nav_menus'=>array_map(function($t){return [$t->term_id,$t->name,$t->count];}, wp_get_nav_menus()),
  'menu_locations'=>get_nav_menu_locations(),
  'widget_options'=>$wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'widget\\_%'"),
  'theme_mods_keys'=>array_keys((array)get_theme_mods())])."\n";
echo "BK ".json_encode(['dir'=>$bdir,'files'=>count(glob("$bdir/posts/*.html")),'bytes'=>array_sum(array_map('filesize', glob("$bdir/posts/*.html")))])."\n";
