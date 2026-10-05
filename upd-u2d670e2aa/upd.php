<?php
// One-shot pensiun.pro updater (bagi hasil sawah): featured photo + excerpt + live check. CLI only.
if (php_sapi_name() !== 'cli') { http_response_code(404); exit; }
$EXPECT_SHA = 'b2edde6be38e28511afe4f34d3b980ada5326047a8c9c43250dbfdf9b5851306';
$dir = __DIR__;
$_SERVER['HTTP_HOST'] = 'pensiun.pro'; $_SERVER['SERVER_NAME'] = 'pensiun.pro';
$_SERVER['REQUEST_URI'] = '/'; $_SERVER['HTTPS'] = 'on'; $_SERVER['SERVER_PORT'] = 443;
@set_time_limit(300); @ini_set('memory_limit', '512M');
$out = ['ok'=>false];
try {
  $files = glob($dir.'/e[0-9][0-9].txt'); sort($files); $blob = '';
  foreach ($files as $fp) $blob .= trim((string)file_get_contents($fp));
  if (hash('sha256', $blob) !== $EXPECT_SHA) throw new Exception('checksum mismatch len='.strlen($blob));
  $p = json_decode(gzdecode(base64_decode($blob)), true);
  if (!is_array($p) || empty($p['image_b64'])) throw new Exception('bad payload');
  require '/home/u814079684/domains/pensiun.pro/public_html/wp-load.php';
  require_once ABSPATH . 'wp-admin/includes/file.php';
  require_once ABSPATH . 'wp-admin/includes/media.php';
  require_once ABSPATH . 'wp-admin/includes/image.php';
  wp_set_current_user(1);
  $q = get_posts(['name'=>$p['slug'],'post_type'=>'post','post_status'=>'any','numberposts'=>1]);
  if (!$q) throw new Exception('post not found');
  $pid = (int)$q[0]->ID;
  $out['old_featured_id'] = (int)get_post_thumbnail_id($pid);
  // excerpt only (content untouched)
  global $wpdb;
  $wpdb->update($wpdb->posts, ['post_excerpt'=>$p['excerpt']], ['ID'=>$pid], ['%s'], ['%d']);
  clean_post_cache($pid);
  // image: webp -> jpeg (q88) when GD supports it, else keep webp
  $webp = base64_decode($p['image_b64']);
  $tmp = wp_tempnam($p['image_basename']);
  $name = $p['image_basename'].'.webp';
  if (function_exists('imagecreatefromwebp')) {
    $src = wp_tempnam('src'); file_put_contents($src, $webp);
    $gd = @imagecreatefromwebp($src); @unlink($src);
    if ($gd) { imagejpeg($gd, $tmp, 88); imagedestroy($gd); $name = $p['image_basename'].'.jpg'; }
    else file_put_contents($tmp, $webp);
  } else file_put_contents($tmp, $webp);
  $out['upload_name'] = $name;
  $aid = media_handle_sideload(['name'=>$name, 'tmp_name'=>$tmp], $pid, get_the_title($pid));
  if (is_wp_error($aid)) { @unlink($tmp); throw new Exception('sideload: '.$aid->get_error_message()); }
  set_post_thumbnail($pid, $aid);
  update_post_meta($aid, '_wp_attachment_image_alt', $p['alt']);
  clean_post_cache($pid);
  $f = get_post($pid); $th = (int)get_post_thumbnail_id($pid);
  $out = array_merge($out, ['ok'=>true, 'id'=>$pid, 'url'=>get_permalink($pid),
    'excerpt'=>$f->post_excerpt, 'excerpt_len'=>mb_strlen($f->post_excerpt, 'UTF-8'),
    'featured_id'=>$th, 'featured_url'=>wp_get_attachment_url($th),
    'alt'=>get_post_meta($th, '_wp_attachment_image_alt', true),
    'categories'=>wp_get_post_categories($pid, ['fields'=>'names']),
    'seo_title'=>get_post_meta($pid,'_pensiun_pro_seo_title',true),
    'meta_description'=>get_post_meta($pid,'_pensiun_pro_meta_description',true),
    'focus_keyword'=>get_post_meta($pid,'_pensiun_pro_focus_keyword',true),
    'author'=>(int)$f->post_author, 'status'=>$f->post_status]);
  // live check
  if (function_exists('wp_cache_flush')) wp_cache_flush();
  $r = wp_remote_get(get_permalink($pid).'?nocache='.time(), ['timeout'=>30, 'sslverify'=>false]);
  if (is_wp_error($r)) { $out['live_error'] = $r->get_error_message(); }
  else {
    $h = wp_remote_retrieve_body($r);
    $out['http'] = (int)wp_remote_retrieve_response_code($r);
    $out['html_len'] = strlen($h);
    preg_match('#<title>(.*?)</title>#s', $h, $m); $out['title_tag'] = $m[1] ?? '';
    preg_match('#<meta[^>]+name=["\']description["\'][^>]*>#i', $h, $m); $out['meta_desc_tag'] = $m[0] ?? '';
    preg_match('#<meta[^>]+property=["\']og:image["\'][^>]*>#i', $h, $m); $out['og_image_tag'] = $m[0] ?? '';
    preg_match('#<link[^>]+rel=["\']canonical["\'][^>]*>#i', $h, $m); $out['canonical_tag'] = $m[0] ?? '';
    $out['faqpage'] = stripos($h, 'FAQPage') !== false;
    $out['ldjson_count'] = substr_count($h, 'application/ld+json');
    $out['has_pensiun_artikel'] = strpos($h, 'pensiun-artikel') !== false;
    $out['has_scoped_style'] = strpos($h, '.pensiun-artikel') !== false;
    $out['has_calc_script'] = strpos($h, 'calc5B') !== false;
    $base = preg_replace('#\.(jpg|webp|png)$#', '', basename((string)$out['featured_url']));
    $out['featured_in_html'] = $base !== '' && strpos($h, $base) !== false;
    $out['h1_count'] = preg_match_all('#<h1[\s>]#i', $h);
  }
} catch (Throwable $e) { $out['error'] = $e->getMessage(); }
foreach ((array)glob($dir.'/e[0-9][0-9].txt') as $fp) @unlink($fp); @unlink(__FILE__);
echo "PPRESULT " . json_encode($out, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n";
