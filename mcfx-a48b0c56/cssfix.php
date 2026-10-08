<?php
// pensiun.pro mobile-css-fix lib (2026-10-08): repair at-rules wrongly prefixed by the old
// scope_css bug (".pensiun-artikel @media ... { raw rules }") and prefix unscoped selectors.
const PPC_SCOPE = '.pensiun-artikel';
const PPC_NESTABLE = ['media','supports','container','layer','document'];

function ppc_skip($s, $i, $n) { // skip whitespace only
  while ($i < $n && ctype_space($s[$i])) $i++;
  return $i;
}
function ppc_scan_to($s, $i, $n, $stops) { // scan to first char in $stops, skipping strings/comments/parens
  $par = 0;
  while ($i < $n) {
    $c = $s[$i];
    if ($c === '/' && $i+1 < $n && $s[$i+1] === '*') { $e = strpos($s, '*/', $i+2); if ($e === false) return $n; $i = $e+2; continue; }
    if ($c === '"' || $c === "'") { $j = $i+1; while ($j < $n && $s[$j] !== $c) { if ($s[$j] === '\\') $j++; $j++; } $i = $j+1; continue; }
    if ($c === '(') $par++; elseif ($c === ')') $par--;
    elseif ($par <= 0 && strpos($stops, $c) !== false) return $i;
    $i++;
  }
  return $n;
}
function ppc_match($s, $open, $n) { // index of matching '}' for '{' at $open
  $d = 0; $i = $open;
  while ($i < $n) {
    $c = $s[$i];
    if ($c === '/' && $i+1 < $n && $s[$i+1] === '*') { $e = strpos($s, '*/', $i+2); if ($e === false) return $n-1; $i = $e+2; continue; }
    if ($c === '"' || $c === "'") { $j = $i+1; while ($j < $n && $s[$j] !== $c) { if ($s[$j] === '\\') $j++; $j++; } $i = $j+1; continue; }
    if ($c === '{') $d++;
    elseif ($c === '}') { $d--; if ($d === 0) return $i; }
    $i++;
  }
  return $n-1;
}
function ppc_parse($s, $a, $b) { // items between [$a,$b)
  $items = []; $i = $a;
  while ($i < $b) {
    $i = ppc_skip($s, $i, $b); if ($i >= $b) break;
    if ($s[$i] === '/' && $i+1 < $b && $s[$i+1] === '*') { $e = strpos($s, '*/', $i+2); $e = ($e === false || $e+2 > $b) ? $b : $e+2; $items[] = ['t'=>'comment','ps'=>$i,'pe'=>$e]; $i = $e; continue; }
    $j = ppc_scan_to($s, $i, $b, $s[$i] === '@' ? '{;' : '{');
    if ($j >= $b) { $items[] = ['t'=>'junk','ps'=>$i,'pe'=>$b]; break; }
    if ($s[$j] === ';') { $items[] = ['t'=>'atstmt','ps'=>$i,'pe'=>$j+1]; $i = $j+1; continue; }
    $k = ppc_match($s, $j, $b);
    $items[] = ['t'=>($s[$i] === '@' ? 'atblock' : 'rule'),'ps'=>$i,'pe'=>$j,'bs'=>$j,'be'=>$k];
    $i = $k+1;
  }
  return $items;
}
function ppc_nocomment($p) { return preg_replace('#/\*.*?\*/#s', ' ', $p); }
function ppc_split($p) { // split selector list at top-level commas
  $out = []; $d = 0; $cur = '';
  for ($i = 0, $n = strlen($p); $i < $n; $i++) { $c = $p[$i];
    if ($c === '(' || $c === '[') $d++; elseif ($c === ')' || $c === ']') $d--;
    if ($c === ',' && $d === 0) { $out[] = $cur; $cur = ''; continue; }
    $cur .= $c; }
  $out[] = $cur; return $out;
}
function ppc_is_scoped($sel) { return (bool)preg_match('/^\s*\.pensiun-artikel(?![\w-])/', ppc_nocomment($sel)); }
function ppc_scope_sel($sel) {
  $t = trim(ppc_nocomment($sel));
  if ($t === '') return $t;
  if (ppc_is_scoped($t)) return $t;
  $t = preg_replace('/^(?::root|html|body)(?![\w-])/', PPC_SCOPE, $t);
  if (ppc_is_scoped($t)) return $t;
  if ($t === '*') return PPC_SCOPE.', '.PPC_SCOPE.' *';
  return PPC_SCOPE.' '.$t;
}
function ppc_at_name($p) { return preg_match('/^@([\w-]+)/', ltrim($p), $m) ? strtolower($m[1]) : ''; }
function ppc_is_bad_at($p) { return (bool)preg_match('/^\s*\.pensiun-artikel\s+@/', ppc_nocomment($p)); }
function ppc_unbad($p) { // ".pensiun-artikel @media screen, .pensiun-artikel print " -> "@media screen, print"
  $p = trim(ppc_nocomment($p));
  $p = preg_replace('/^\.pensiun-artikel\s+/', '', $p);
  return preg_replace('/,\s*\.pensiun-artikel\s+/', ', ', $p);
}

// analyse/fix a CSS range; returns [stats, edits]
function ppc_walk($s, $a, $b, $inNest, &$st, &$edits, $fix) {
  foreach (ppc_parse($s, $a, $b) as $it) {
    if ($it['t'] === 'rule') {
      $p = substr($s, $it['ps'], $it['pe'] - $it['ps']);
      if (ppc_is_bad_at($p)) {
        $at = ppc_unbad($p); $name = ppc_at_name($at);
        $st['bad_at']++; $st['bad_preludes'][] = $at;
        if ($fix) $edits[] = [$it['ps'], $it['pe'], $at.' '];
        if (in_array($name, PPC_NESTABLE, true)) ppc_walk($s, $it['bs']+1, $it['be'], true, $st, $edits, $fix);
        continue;
      }
      $parts = ppc_split($p); $bad = 0;
      foreach ($parts as $q) if (trim(ppc_nocomment($q)) !== '' && !ppc_is_scoped($q)) $bad++;
      if ($bad) {
        if ($inNest) $st['unpref_nested'] += $bad; else $st['unpref_top'] += $bad;
        if (count($st['samples']) < 6) $st['samples'][] = trim(substr(preg_replace('/\s+/', ' ', ppc_nocomment($p)), 0, 70));
        if ($fix) $edits[] = [$it['ps'], $it['pe'], implode(', ', array_map('ppc_scope_sel', $parts)).' '];
      }
      if ($inNest) $st['nested_rules']++;
    } elseif ($it['t'] === 'atblock') {
      $p = substr($s, $it['ps'], $it['pe'] - $it['ps']); $name = ppc_at_name($p);
      if ($name === 'media') $st['media_ok']++;
      if (in_array($name, PPC_NESTABLE, true)) ppc_walk($s, $it['bs']+1, $it['be'], true, $st, $edits, $fix);
    } elseif ($it['t'] === 'junk') { $st['junk']++; }
  }
}
function ppc_stats0() { return ['bad_at'=>0,'media_ok'=>0,'unpref_top'=>0,'unpref_nested'=>0,'nested_rules'=>0,'junk'=>0,'bad_preludes'=>[],'samples'=>[]]; }
function ppc_fix_css($css, &$st) {
  $edits = []; ppc_walk($css, 0, strlen($css), false, $st, $edits, true);
  usort($edits, function($x, $y){ return $y[0] <=> $x[0]; });
  foreach ($edits as $e) $css = substr($css, 0, $e[0]).$e[2].substr($css, $e[1]);
  return $css;
}
// process all <style> blocks in a post_content
function ppc_content($content, $fix) {
  $st = ppc_stats0(); $st['styles'] = 0; $st['media_total'] = substr_count($content, '@media');
  if (!preg_match_all('#(<style\b[^>]*>)(.*?)(</style>)#s', $content, $m, PREG_OFFSET_CAPTURE)) return [$content, $st];
  $out = $content;
  for ($k = count($m[2]) - 1; $k >= 0; $k--) {
    $st['styles']++;
    $css = $m[2][$k][0]; $off = $m[2][$k][1];
    if ($fix) { $new = ppc_fix_css($css, $st); $out = substr($out, 0, $off).$new.substr($out, $off + strlen($css)); }
    else { $e = []; ppc_walk($css, 0, strlen($css), false, $st, $e, false); }
  }
  $st['bad_preludes'] = array_values(array_unique($st['bad_preludes']));
  return [$out, $st];
}
function ppc_strip_styles($c) { return preg_replace('#<style\b[^>]*>.*?</style>#s', '<style></style>', $c); }
