<?php
// One-shot pensiun.pro publisher (dari hobi ke cuan). CLI only (Hostinger cron). Self-deletes.
if (php_sapi_name() !== 'cli') { http_response_code(404); exit; }
$EXPECT_SHA = '37f2dbeb81d37953b40a72a939918732759b271e7f316113cc63022625570fbe';
$dir = __DIR__;
$_SERVER['HTTP_HOST'] = 'pensiun.pro'; $_SERVER['SERVER_NAME'] = 'pensiun.pro';
$_SERVER['REQUEST_URI'] = '/'; $_SERVER['HTTPS'] = 'on'; $_SERVER['SERVER_PORT'] = 443;
@set_time_limit(300); @ini_set('memory_limit', '512M');
$out = ['ok'=>false];
try {
  $files = glob($dir.'/d[0-9][0-9].txt'); sort($files); $blob = '';
  foreach ($files as $fp) $blob .= trim((string)file_get_contents($fp));
  if (hash('sha256', $blob) !== $EXPECT_SHA) throw new Exception('checksum mismatch len='.strlen($blob));
  $p = json_decode(gzdecode(base64_decode($blob)), true);
  if (!is_array($p) || empty($p['content'])) throw new Exception('bad payload');

  require '/home/u814079684/domains/pensiun.pro/public_html/wp-load.php';
  require_once ABSPATH . 'wp-admin/includes/file.php';
  require_once ABSPATH . 'wp-admin/includes/media.php';
  require_once ABSPATH . 'wp-admin/includes/image.php';
  wp_set_current_user(1);

  $cat_ids = [];
  foreach ([19=>'Usaha', 45=>'Ide Usaha'] as $id=>$nm) {
    $t = get_term($id, 'category');
    if ($t && !is_wp_error($t)) $cat_ids[] = $id;
    else { $e = term_exists($nm, 'category'); if ($e) $cat_ids[] = (int)(is_array($e)?$e['term_id']:$e); }
  }
  $hobi = term_exists('Hobi', 'category');
  $out['hobi_existed'] = (bool)$hobi;
  if (!$hobi) $hobi = wp_insert_term('Hobi', 'category', ['slug'=>'hobi']);
  if ($hobi && !is_wp_error($hobi)) $cat_ids[] = (int)(is_array($hobi)?$hobi['term_id']:$hobi);
  $cat_ids = array_values(array_unique($cat_ids));

  $content = $p['content'];
  $bc = 'https://pensiun.pro/usaha-untuk-pensiunan/';
  if (!get_page_by_path('usaha-untuk-pensiunan', OBJECT, ['page','post'])) {
    $link = get_category_link(19);
    if ($link && !is_wp_error($link)) { $content = str_replace($bc, $link, $content); $out['breadcrumb_fixed_to'] = $link; }
  }

  $existing = get_posts(['name'=>$p['slug'],'post_type'=>'post','post_status'=>'any','numberposts'=>1]);
  kses_remove_filters();
  $arr = ['post_title'=>$p['post_title'],'post_name'=>$p['slug'],'post_content'=>'<!-- pending -->',
          'post_excerpt'=>$p['excerpt'],'post_status'=>'publish','post_author'=>1,'post_type'=>'post',
          'post_category'=>$cat_ids];
  if ($existing) { $arr['ID'] = (int)$existing[0]->ID; $pid = wp_update_post($arr, true); $out['action']='updated'; }
  else { $pid = wp_insert_post($arr, true); $out['action']='inserted'; }
  if (is_wp_error($pid)) throw new Exception('insert: '.$pid->get_error_message());
  $pid = (int)$pid;
  global $wpdb;
  if ($wpdb->update($wpdb->posts, ['post_content'=>$content], ['ID'=>$pid], ['%s'], ['%d']) === false)
    throw new Exception('wpdb: '.$wpdb->last_error);
  clean_post_cache($pid);
  update_post_meta($pid, '_pensiun_pro_seo_title', $p['seo_title']);
  update_post_meta($pid, '_pensiun_pro_meta_description', $p['meta_description']);
  update_post_meta($pid, '_pensiun_pro_focus_keyword', $p['focus_keyword']);
  delete_post_meta($pid, '_pensiun_pro_faq');

  $tmp = wp_tempnam($p['image_filename']);
  file_put_contents($tmp, base64_decode($p['image_b64']));
  $aid = media_handle_sideload(['name'=>$p['image_filename'], 'tmp_name'=>$tmp], $pid, $p['post_title']);
  if (is_wp_error($aid)) { @unlink($tmp); $out['featured_error'] = $aid->get_error_message(); }
  else { set_post_thumbnail($pid, $aid); update_post_meta($aid, '_wp_attachment_image_alt', $p['alt']); }

  $f = get_post($pid); $th = (int)get_post_thumbnail_id($pid);
  $out = array_merge($out, [
    'ok'=>true, 'id'=>$pid, 'slug'=>$f->post_name, 'url'=>get_permalink($pid), 'author'=>(int)$f->post_author,
    'status'=>$f->post_status, 'excerpt_len'=>mb_strlen($f->post_excerpt),
    'categories'=>wp_get_post_categories($pid, ['fields'=>'names']), 'category_ids'=>wp_get_post_categories($pid),
    'featured_id'=>$th, 'featured_url'=>$th ? wp_get_attachment_url($th) : '',
    'alt'=>$th ? get_post_meta($th, '_wp_attachment_image_alt', true) : '',
    'seo_title'=>get_post_meta($pid,'_pensiun_pro_seo_title',true),
    'meta_description'=>get_post_meta($pid,'_pensiun_pro_meta_description',true),
    'focus_keyword'=>get_post_meta($pid,'_pensiun_pro_focus_keyword',true),
    'content_len'=>strlen($f->post_content), 'faq_in_content'=>strpos($f->post_content,'FAQPage')!==false,
  ]);
} catch (Throwable $e) { $out['error'] = $e->getMessage(); }
foreach ((array)glob($dir.'/d[0-9][0-9].txt') as $fp) @unlink($fp); @unlink(__FILE__);
echo "PPRESULT " . json_encode($out, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n";
