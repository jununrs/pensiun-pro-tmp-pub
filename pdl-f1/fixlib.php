<?php
function ppl_rewrite($s, $map, &$hits) {
  $hits = [];
  return preg_replace_callback('~<a\b[^>]*>~i', function($m) use ($map, &$hits) {
    return preg_replace_callback('~(\bhref\s*=\s*)(["\'])(.*?)\2~is', function($h) use ($map, &$hits) {
      if (isset($map[$h[3]])) { $hits[] = $h[3]; return $h[1].$h[2].$map[$h[3]].$h[2]; }
      return $h[0];
    }, $m[0], 1);
  }, $s);
}
function ppl_neutral($s) {
  return preg_replace_callback('~<a\b[^>]*>~i', function($m) {
    return preg_replace('~(\bhref\s*=\s*)(["\'])(.*?)\2~is', '$1$2#$2', $m[0], 1);
  }, $s);
}
function ppl_anchor_panduan($s) {
  $n = 0; $l = [];
  if (preg_match_all('~<a\b[^>]*>~i', $s, $m)) foreach ($m[0] as $t)
    if (preg_match('~\bhref\s*=\s*(["\'])((?:https?:)?(?://(?:www\.)?pensiun\.pro)?/panduan/.*?)\1~is', $t, $h)) { $n++; $l[] = $h[2]; }
  return [$n, $l];
}
