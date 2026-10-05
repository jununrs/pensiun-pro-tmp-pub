<?php
@set_time_limit(300);
@ini_set('memory_limit', '512M');
if (!isset($_SERVER['HTTP_HOST'])) { $_SERVER['HTTP_HOST'] = 'pensiun.pro'; $_SERVER['HTTPS'] = 'on'; $_SERVER['REQUEST_URI'] = '/'; }
require __DIR__ . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$posts = json_decode(file_get_contents(__DIR__ . '/_pp_parkir_payload.json'), true);
if (!is_array($posts)) {
  echo json_encode(['ok'=>false,'error'=>'Invalid payload']);
  @unlink(__DIR__ . '/_pp_parkir_payload.json');
@unlink(__FILE__);
  exit;
}

$user = get_user_by('login', 'joen');
if (!$user) {
  foreach (get_users(['number'=>50]) as $cand) {
    if (strcasecmp($cand->display_name, 'joen') === 0 || stripos($cand->user_login, 'joen') !== false) {
      $user = $cand; break;
    }
  }
}
if (!$user) $user = get_user_by('id', 1);
if ($user) wp_set_current_user($user->ID);
$author_id = 1;

function pensiun_ensure_cat($name) {
  $term = term_exists($name, 'category');
  if ($term) return (int)(is_array($term) ? $term['term_id'] : $term);
  $r = wp_insert_term($name, 'category', ['slug' => sanitize_title($name)]);
  if (is_wp_error($r)) return 0;
  return (int)$r['term_id'];
}

function pensiun_attach_png_gd($post_id, $alt, $title) {
  if (!function_exists('imagecreatetruecolor')) {
    return new WP_Error('no_gd', 'GD missing');
  }
  $w = 1280; $h = 720;
  $im = imagecreatetruecolor($w, $h);
  $deep = imagecolorallocate($im, 11, 45, 85);
  $navy = imagecolorallocate($im, 18, 63, 115);
  $teal = imagecolorallocate($im, 0, 140, 149);
  $bright = imagecolorallocate($im, 0, 167, 165);
  $aqua = imagecolorallocate($im, 56, 196, 193);
  $light = imagecolorallocate($im, 138, 219, 213);
  $white = imagecolorallocate($im, 255, 255, 255);
  imagefilledrectangle($im, 0, 0, $w, $h, $deep);
  imagefilledrectangle($im, 0, 180, $w, $h, $navy);
  imagefilledrectangle($im, 0, 320, $w, $h, $teal);
  imagefilledrectangle($im, 0, 460, $w, $h, $bright);
  imagefilledrectangle($im, 0, 580, $w, $h, $aqua);
  imagefilledrectangle($im, 60, 100, 600, 520, $white);
  imagestring($im, 5, 90, 140, 'Usaha Parkir Lahan Kosong', $deep);
  imagestring($im, 4, 90, 180, 'Ide usaha parkir untuk pensiunan', $teal);
  imagefilledrectangle($im, 720, 160, 1200, 520, $teal);
  for ($i = 0; $i < 4; $i++) {
    $x = 760 + $i * 100;
    imagerectangle($im, $x, 220, $x + 70, 460, $light);
  }
  $tmp = wp_tempnam('parkir-featured.png');
  $png = $tmp . '.png';
  imagepng($im, $png);
  imagedestroy($im);
  @unlink($tmp);
  $file_array = ['name' => 'usaha-parkir-lahan-kosong-featured.png', 'tmp_name' => $png];
  $attach_id = media_handle_sideload($file_array, $post_id);
  if (is_wp_error($attach_id)) {
    @unlink($png);
    return $attach_id;
  }
  set_post_thumbnail($post_id, $attach_id);
  update_post_meta($attach_id, '_wp_attachment_image_alt', $alt);
  wp_update_post(['ID'=>$attach_id, 'post_title'=>$title.' — featured']);
  return (int)$attach_id;
}

kses_remove_filters();
remove_filter('content_save_pre', 'wp_filter_post_kses');
remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');

global $wpdb;
$results = [];

foreach ($posts as $p) {
  $item = ['filename'=>$p['filename'], 'slug'=>$p['slug']];
  try {
    $cat_ids = [];
    foreach ($p['categories'] as $cname) {
      $cid = pensiun_ensure_cat($cname);
      if ($cid) $cat_ids[] = $cid;
    }

    $existing = null;
    $q = get_posts(['name'=>$p['slug'], 'post_type'=>'post', 'post_status'=>'any', 'numberposts'=>1]);
    if ($q) $existing = $q[0];

    $placeholder = '<!-- pensiun-pro pending content -->';
    $postarr = [
      'post_title'   => $p['post_title'],
      'post_name'    => $p['slug'],
      'post_content' => $placeholder,
      'post_excerpt' => $p['excerpt'],
      'post_status'  => 'publish',
      'post_author'  => $author_id,
      'post_type'    => 'post',
      'post_category'=> $cat_ids,
    ];
    if ($existing) {
      $postarr['ID'] = (int)$existing->ID;
      $post_id = wp_update_post($postarr, true);
      $item['action'] = 'updated';
    } else {
      $post_id = wp_insert_post($postarr, true);
      $item['action'] = 'inserted';
    }
    if (is_wp_error($post_id)) {
      $item['ok']=false; $item['error']=$post_id->get_error_message(); $results[]=$item; continue;
    }
    $post_id = (int)$post_id;

    $content = $p['content'];
    $ok = $wpdb->update(
      $wpdb->posts,
      ['post_content' => $content],
      ['ID' => $post_id],
      ['%s'],
      ['%d']
    );
    if ($ok === false) {
      $item['ok']=false;
      $item['error']='wpdb update failed: '.$wpdb->last_error;
      $item['post_id']=$post_id;
      $results[]=$item;
      continue;
    }
    clean_post_cache($post_id);

    update_post_meta($post_id, '_pensiun_pro_seo_title', $p['seo_title']);
    update_post_meta($post_id, '_pensiun_pro_meta_description', $p['meta_description']);
    if (!empty($p['focus_keyword'])) {
      update_post_meta($post_id, '_pensiun_pro_focus_keyword', $p['focus_keyword']);
    }
    delete_post_meta($post_id, '_pensiun_pro_faq');

    $attach_id = null;
    $featured_note = '';
    if (!empty($p['image_b64'])) {
      $bin = base64_decode($p['image_b64']);
      $tmp = wp_tempnam($p['image_filename']);
      file_put_contents($tmp, $bin);
      $file_array = ['name'=>$p['image_filename'], 'tmp_name'=>$tmp];
      $attach_id = media_handle_sideload($file_array, $post_id);
      if (is_wp_error($attach_id)) {
        $featured_note = 'sideload_failed: '.$attach_id->get_error_message();
        @unlink($tmp);
        $attach_id = pensiun_attach_png_gd($post_id, $p['alt'], $p['post_title']);
        if (is_wp_error($attach_id)) {
          $item['featured_error'] = $featured_note.' | gd: '.$attach_id->get_error_message();
          $attach_id = null;
        } else {
          $featured_note = 'gd_png_fallback';
        }
      } else {
        set_post_thumbnail($post_id, $attach_id);
        update_post_meta($attach_id, '_wp_attachment_image_alt', $p['alt']);
        wp_update_post(['ID'=>$attach_id, 'post_title'=>$p['post_title'].' — featured']);
      }
    } else {
      $attach_id = pensiun_attach_png_gd($post_id, $p['alt'], $p['post_title']);
      if (is_wp_error($attach_id)) {
        $item['featured_error'] = $attach_id->get_error_message();
        $attach_id = null;
      } else {
        $featured_note = 'gd_png_only';
      }
    }

    $term_names = [];
    foreach ($cat_ids as $cid) {
      $t = get_term($cid, 'category');
      if ($t && !is_wp_error($t)) $term_names[] = $t->name;
    }

    $fresh = get_post($post_id);
    $pc = $fresh ? $fresh->post_content : '';
    $stored_excerpt = $fresh ? $fresh->post_excerpt : $p['excerpt'];
    $item['ok'] = true;
    $item['post_id'] = $post_id;
    $item['url'] = get_permalink($post_id);
    $item['featured_id'] = $attach_id ? (int)$attach_id : 0;
    $item['featured_ok'] = (bool)$attach_id;
    $item['featured_note'] = $featured_note;
    $item['alt'] = $p['alt'];
    $item['categories'] = $term_names;
    $item['seo_title'] = $p['seo_title'];
    $item['meta_description'] = $p['meta_description'];
    $item['focus_keyword'] = $p['focus_keyword'];
    $item['excerpt'] = $stored_excerpt;
    $item['excerpt_len'] = mb_strlen($stored_excerpt);
    $item['author_id'] = $author_id;
    $item['content_len'] = strlen($pc);
    $item['has_style'] = (strpos($pc, '<style') !== false) && (strpos($pc, 'pensiun-artikel') !== false);
    $item['has_scoped_table'] = (strpos($pc, '<table') !== false);
    $item['has_script'] = (strpos($pc, '<script') !== false);
    $item['faq_meta'] = get_post_meta($post_id, '_pensiun_pro_faq', true);
    $item['faq_in_content'] = (strpos($pc, 'FAQPage') !== false);
    $results[] = $item;
  } catch (Throwable $e) {
    $item['ok']=false; $item['error']=$e->getMessage(); $results[]=$item;
  }
}

$ok_count = 0;
foreach ($results as $r) { if (!empty($r['ok'])) $ok_count++; }
echo json_encode([
  'ok' => $ok_count === count($results),
  'author_id' => $author_id,
  'user_id' => get_current_user_id(),
  'count' => count($results),
  'updated' => $ok_count,
  'results' => $results,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
@unlink(__DIR__ . '/_pp_parkir_payload.json');
@unlink(__FILE__);
