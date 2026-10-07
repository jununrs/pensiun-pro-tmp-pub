<?php
if (php_sapi_name() !== 'cli') { http_response_code(404); exit; }
@ini_set('display_errors','1'); error_reporting(E_ALL);
require '/home/u814079684/domains/pensiun.pro/public_html/wp-load.php';
$p=get_post(303);
if(!$p){ echo "VERIFY {\"ok\":false,\"error\":\"no post\"}\n"; exit; }
$c=$p->post_content;
preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s',$c,$m);
$bcs=[]; $faq=false; $missing=[];
foreach($m[1] as $j){
  $d=json_decode(trim($j),true);
  if(!$d) continue;
  $nodes=isset($d['@graph'])?$d['@graph']:[$d];
  foreach($nodes as $n){
    $t=$n['@type']??'';
    if($t==='FAQPage') $faq=true;
    if($t==='BreadcrumbList'){
      $els=$n['itemListElement']??[];
      $ok=true; $items=[];
      foreach($els as $li){
        $has=isset($li['item']) && is_string($li['item']) && $li['item']!=='';
        if(!$has){$ok=false;$missing[]=$li;}
        $items[]=['position'=>$li['position']??null,'name'=>$li['name']??null,'item'=>$li['item']??null,'has_item'=>$has];
      }
      $bcs[]=['source'=>'post_content','@id'=>$n['@id']??null,'all_have_item'=>$ok,'elements'=>$items];
    }
  }
}
$plugins=get_option('active_plugins',[]);
$seo=['yoast'=>false,'rankmath'=>false,'aioseo'=>false,'seopress'=>false,'plugins'=>$plugins];
foreach($plugins as $pl){
  if(stripos($pl,'wordpress-seo')!==false||stripos($pl,'yoast')!==false)$seo['yoast']=true;
  if(stripos($pl,'seo-by-rank-math')!==false||stripos($pl,'rank-math')!==false)$seo['rankmath']=true;
  if(stripos($pl,'all-in-one-seo')!==false)$seo['aioseo']=true;
  if(stripos($pl,'wp-seopress')!==false)$seo['seopress']=true;
}
$theme=wp_get_theme();
$hits=[];
foreach([get_template_directory().'/functions.php', get_stylesheet_directory().'/functions.php'] as $f){
  if(is_file($f) && stripos(file_get_contents($f),'BreadcrumbList')!==false) $hits[]=$f;
}
$rm_bc=null; $yoast_bc=null;
if($seo['rankmath']){
  $rm=get_option('rank-math-options-general',[]);
  $rm_bc=['breadcrumbs'=>$rm['breadcrumbs']??null];
}
if($seo['yoast']){
  $yt=get_option('wpseo_titles',[]);
  $yoast_bc=['breadcrumbs-enable'=>$yt['breadcrumbs-enable']??null];
}
$rendered_bcs=[]; $rendered_count=0; $code=0; $curl_err='';
$url=get_permalink(303);
$ch=curl_init($url);
curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>30,CURLOPT_USERAGENT=>'pp-bc-verify',CURLOPT_SSL_VERIFYPEER=>false,CURLOPT_HTTPHEADER=>['Cache-Control: no-cache']]);
$html=curl_exec($ch); $code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE); $curl_err=curl_error($ch); curl_close($ch);
if(is_string($html) && $html!==''){
  preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/si',$html,$hm);
  foreach($hm[1] as $j){
    $d=json_decode(trim($j),true);
    if(!$d) continue;
    $nodes=isset($d['@graph'])?$d['@graph']:[$d];
    $flat=[];
    foreach($nodes as $n){
      if(!is_array($n)) continue;
      $t=$n['@type']??'';
      if(is_array($t)){ foreach($t as $tt){ $nn=$n; $nn['@type']=$tt; $flat[]=$nn; } }
      else $flat[]=$n;
    }
    foreach($flat as $n){
      if(($n['@type']??'')!=='BreadcrumbList') continue;
      $rendered_count++;
      $els=$n['itemListElement']??[];
      $items=[]; $ok=true;
      foreach($els as $li){
        $itemVal=$li['item']??null;
        $has=false;
        if(is_string($itemVal) && $itemVal!=='') $has=true;
        elseif(is_array($itemVal) && !empty($itemVal['@id'])) { $has=true; $itemVal=$itemVal['@id']; }
        if(!$has) $ok=false;
        $items[]=['position'=>$li['position']??null,'name'=>$li['name']??null,'item'=>$itemVal,'has_item'=>$has];
      }
      $rendered_bcs[]=['source'=>'rendered_html','@id'=>$n['@id']??null,'all_have_item'=>$ok,'elements'=>$items];
    }
  }
}
$stale=strpos($c,'jadi-guru-les-privat-setelah-pensiun')!==false;
$cat=strpos($c,'https://pensiun.pro/category/usaha/')!==false;
$contrast=strpos($c,'#0B2D55')!==false;
$o=[
  'id'=>(int)$p->ID,'slug'=>$p->post_name,'url'=>get_permalink(303),'content_len'=>strlen($c),
  'faqpage'=>$faq,'stale_slug'=>$stale,'category_usaha_item'=>$cat,'contrast_kept'=>$contrast,
  'content_breadcrumbs'=>$bcs,'all_listitems_have_item'=>empty($missing)&&count($bcs)>0,
  'active_seo'=>$seo,'theme'=>['name'=>$theme->get('Name'),'stylesheet'=>$theme->get_stylesheet()],
  'theme_functions_breadcrumblist'=>$hits,
  'rankmath_bc'=>$rm_bc,'yoast_bc'=>$yoast_bc,
  'rendered_http'=>$code,'curl_err'=>$curl_err,'rendered_breadcrumb_count'=>$rendered_count,'rendered_breadcrumbs'=>$rendered_bcs,
  'ok'=>empty($missing)&&count($bcs)>0&&!$stale&&$cat&&!$faq
];
echo "VERIFY ".json_encode($o, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
