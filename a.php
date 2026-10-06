<?php
$L="/home/u814079684/domains/pensiun.pro/public_html/_pprst.log";
file_put_contents($L,"start\n");
$_SERVER["HTTP_HOST"]="pensiun.pro";$_SERVER["SERVER_NAME"]="pensiun.pro";$_SERVER["REQUEST_URI"]="/";$_SERVER["HTTPS"]="on";$_SERVER["SERVER_PORT"]=443;
require "/home/u814079684/domains/pensiun.pro/public_html/wp-load.php";
global $wpdb;
function pp_fix($css){
  $css=preg_replace('#/\*.*?\*/#s','',$css);
  $css=preg_replace_callback('/\.pensiun-artikel\s+(@media[^{]+?)\s*\{((?:[^{}]*\{[^{}]*\})*)\s*\}/s',function($m){
    $out="";
    if(preg_match_all('/([^{}]+)\{([^{}]*)\}/',$m[2],$mm,PREG_SET_ORDER)){
      foreach($mm as $r){
        $sels=array_map("trim",explode(",",$r[1]));
        foreach($sels as &$s){ if(strpos($s,".pensiun-artikel")!==0) $s=".pensiun-artikel ".$s; }
        $out.=implode(", ",$sels)." {".trim($r[2])."}\n";
      }
    }
    return $m[1]." {\n".$out."}";
  },$css);
  $css=preg_replace('/\.pensiun-artikel\s+\.pensiun-artikel(?![\w-])/','.pensiun-artikel',$css);
  return trim(preg_replace("/\n\s*\n+/","\n",$css));
}
$ov=zlib_decode(base64_decode("eNqlWFlv4zYQfu+vIBDsIllYig7Lh/bJiW2g2BbYFn0r+kCZtMQ1TakUlU1q7H8vdTmmTEpKNkEOmORw5ptvLt5/An/iXLxQDJ68EGSQYQoiDhkCXzHLScHsrzwFt/DfAoJjgeAECAzpBDD49HIHPt3/Ymf1PgtyQQ6YniK4O8Q8LRgKKWEYcivmEBHMxK27cBCOJzf72T7a74DzYXKzrb6AO339fzH7cN7iOs6Hu8+7lKY8vHEevHUQ/Li6EiTu5PozT/OZfxoSZZeGaQ/bOaQw0S/Bb/AAWd8xJHqOndWqEdCpVUkxiugF3Q8q0GuTK9Bdz9/O/QbcKOUIc4vivQiD7BnkKSUI3PiLx+mj266Wwoo8dGfZs/zo2coTiNLvoQNcTx7xHfmLxxG8dd3JNJgsgontOndGO4D8nOfwCFXNawn+YuK5S/njSyHTs/NraBp9Qves6PUhP1CNmnaN0unVuMIW6TFKFRK3SHSYc19FTnHEuiAANq/WFDmb1fZh+6DKubbnZrFaP6wDxYLZ66rjrOarQOeWDCJEWBy6dsDxEbj2VP7pOKv0k7e4dpYz0zqrNkLS+hw2zuJxGfRszSbGpVzwlMXDAVhvl/yV5iLypEBoIsHNw3Yz20xbWESahf4QlSUWrS/qeFCxKnmtQ2p6N6R01EVLUuV3LDAHHyUIUBS5njLHco/KmPV2uV0Omqtjgb2Q3v9humU8sI/bzWIzfy+wZi/XQFiHVMCDqolr0OSdcdFzeQQ5yd8Dw6WlCuDAXlawS4//BSN5ldbRcUGJ6ui62o129LSbhUsgXB1bAy1bKwWAgBHFOvOPkMeEhc6wFxtBCYZI/lZkofne30tu9KX3qdep7Z/3KRPWd0ziRIRzx2kNj1IhE3PoXVGv2p+T/7DEf1bmO4qF5LeVZ3BXecXx9GHQaB6l6AUIHjKRWLuEUHSbInQHBJq8+UiigfJNN+MnzN56dXMm0YTQ4N2JmqfOJKvAv+Dger3ZbvrEJWaN0Un14FvEttYm6RPmo3BpdiaasttzDxpdmuoDO5gJkjJdACv1pEoDsov+hinMAS+OMvF/BAjuBeRgurqfPujTA8kFkU3SKYZZaM8MabzZdJXIjSVDzWDdxkifPpX8Nq/birqpMPi0vqRXXzHcS5z3olMH0Ou92kayr9Uab7ruOnzMoICAkolhLZJr41L72t/Mr0uKdw15VVM+Q0piZhGBj3m4k3095r36hWGE9ynHfXq2exR9m2QwbiDpB+RySxcXTa/vndv2+nJNZ39XxdQXSA8FhSLl+gA6nNfHFXe1s7jspsp8sqeyxCYEIcxGjj7OXFt1S7UskhM9V40x1bQAr+29b0gJEUGQxYCWjcdwLmt22xFkomi3++tgNX/s2U5YVugm2WY5xxTvhA51/dRjB0NecNRWh7AcCyCxLweZ0d35pfLhPt0V+ZAJ9a5TWohynA5ZyrBKzte0ccGI8ttvtQpmE3dZ/kjiegszIxKYE2omqqHnHkGHsrttpV+RC8E8wa/sGqbLq7TR5fLijJyrBaRWhHPIf+axQvsY4PQm0WVbtt6gYsfEmppvOn+E9ByEj8v1arPSEqAcfzQMMFZxzRAUDJUyT1/KLu4GdvWncc+gX5WTaroxvw9IXxygOm01yUDn06E6LKNUZtJEB5q56AfvLPr1m5B1wLuOm3S18h0y675VB82eUFnoq/SjFQJjixeCsEFYTadzLHMQMdcjNUU3qA0S5DISDNcIDpkc0rgMej1+ndCTZf9XWsiwlKwzTNXwGJXJZdQLAMUxZgj+TKemNMdB3akt2un/N1jSPdFrSuvFn7l8evngVz/3XXbmZc24kvHDrAqwWapjdrfkjWsNW6HZ6Xoe2q7+KJ/BimOE9d0bwgISOvJZZkQPvQgu4KlfNto6aBquGhVCObYJK91b4iXDPXOsCdxGzN+pXPjH7GxrqMhIsKTeL8N5uUb1PbTq9Bj67KhwzvZqWOvu43/pzvyR"));
$ids=[190=>"cara-menghitung-tht-taspen-pns",299=>"kenaikan-gaji-pensiunan-pns-2026",301=>"tabel-gaji-pensiunan-pns-2026"];
$res=["ok"=>false,"posts"=>[]];
foreach($ids as $id=>$slug){
  $o=["id"=>$id,"slug"=>$slug];
  try{
    $p=get_post($id);
    if(!$p||$p->post_name!==$slug) throw new Exception("target");
    $c=$p->post_content;
    $a=strpos($c,"<style>"); $b=strpos($c,"</style>");
    if($a===false||$b===false) throw new Exception("nostyle");
    $a+=7; $old=substr($c,$a,$b-$a);
    if(strpos($old,"Restyle v2")!==false){ $o["action"]="already"; }
    else {
      update_post_meta($id,"_pp_backup_pre_restyle",wp_slash($c));
      $new=pp_fix($old)."\n".$ov."\n";
      $n=substr($c,0,$a).$new.substr($c,$b);
      if($wpdb->update($wpdb->posts,["post_content"=>$n,"post_modified"=>current_time("mysql"),"post_modified_gmt"=>current_time("mysql",1)],["ID"=>$id],["%s","%s","%s"],["%d"])===false) throw new Exception("wpdb");
      $o["action"]="updated";
    }
    clean_post_cache($id); do_action("litespeed_purge_post",$id);
    $f=get_post($id);
    $o+=["ok"=>true,"url"=>get_permalink($id),"content_len"=>strlen($f->post_content),"has_restyle"=>strpos($f->post_content,"Restyle v2")!==false,"faq"=>strpos($f->post_content,"FAQPage")!==false];
  }catch(Throwable $e){$o["error"]=$e->getMessage();}
  $res["posts"][]=$o;
}
$res["ok"]=!array_filter($res["posts"],function($x){return empty($x["ok"]);});
file_put_contents($L,"PPRESULT ".json_encode($res,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n");
@unlink("/home/u814079684/a.php");
