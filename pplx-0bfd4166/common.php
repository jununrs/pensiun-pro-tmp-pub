<?php
// shared matchers for pensiun.pro link fix (exact old URLs only)
function pplf_patterns() {
  $host = '(?:https?:)?(?:\\\\?\/\\\\?\/(?:www\.)?pensiun\.pro)';
  $sl = '\\\\?\/';
  $mk = function($segs) use ($host, $sl) {
    $path = $sl . implode($sl, $segs) . '(?:' . $sl . ')?';
    // absolute: not preceded by a word char/dot/slash; relative: preceded by quote, =, ( or whitespace
    return '~(?:(?<![\w./-])' . $host . $path . '|(?<=["\'=(\s])' . $path . ')(?=["\'#?\s<>)\\\\,]|$)~i';
  };
  return [
    'kebutuhan' => ['re' => $mk(['panduan', 'menghitung-kebutuhan-bulanan-pensiunan']), 'new' => 'https://pensiun.pro/kalkulator-biaya-cukup-seumur-hidup/'],
    'kontak'    => ['re' => $mk(['kontak']), 'new' => 'https://pensiun.pro/contact/'],
  ];
}
// replacement keeps JSON-escaped form if the match was JSON-escaped
function pplf_replace($s, &$counts, &$matches) {
  foreach (pplf_patterns() as $k => $p) {
    $new = $p['new'];
    $s = preg_replace_callback($p['re'], function($m) use ($new, $k, &$counts, &$matches) {
      $counts[$k] = ($counts[$k] ?? 0) + 1;
      $matches[] = $k . ' :: ' . $m[0];
      return (strpos($m[0], '\\/') !== false) ? str_replace('/', '\\/', $new) : $new;
    }, $s);
  }
  return $s;
}
function pplf_raw_hits($s) {
  return ['kebutuhan' => substr_count($s, 'menghitung-kebutuhan-bulanan-pensiunan'),
          'kontak' => preg_match_all('~kontak(?:\\\\?/|["\'#?])~i', $s)];
}
function pplf_ctx($s, $needle_re, $w = 60) {
  $out = [];
  if (preg_match_all($needle_re, $s, $m, PREG_OFFSET_CAPTURE)) {
    foreach ($m[0] as $x) { $o = $x[1]; $out[] = substr($s, max(0, $o - $w), strlen($x[0]) + 2 * $w); }
  }
  return $out;
}
