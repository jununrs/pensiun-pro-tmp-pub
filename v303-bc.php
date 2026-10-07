<?php
if (php_sapi_name() !== 'cli') { http_response_code(404); exit; }
require '/home/u814079684/domains/pensiun.pro/public_html/wp-load.php';
$p=get_post(303);
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
$seo=['yoast'=>false,'rankmath'=>false,'aioseo'=>false,'seopress'=>false];
foreach($plugins as $pl){
  if(stripos($pl,'wordpress-seo')!==false||stripos($pl,'yoast')!==false)$seo['yoast']=true;
  if(stripos($pl,'seo-by-rank-math')!==false||stripos($pl,'rank-math')!==false)$seo['rankmath']=true;
  if(stripos($pl,'all-in-one-seo')!==false)$seo['aioseo']=true;
  if(stripos($pl,'wp-seopress')!==false)$seo['seopress']=true;
}
$theme=wp_get_theme();
$theme_files_hit=[];
foreach([get_template_directory(), get_stylesheet_directory()] as $root){
  foreach(['functions.php','inc/schema.php','inc/breadcrumbs.php','template-parts/breadcrumbs.php'] as $rel){
    $f=$root.'/'.$rel;
    if(is_file($f) && stripos(file_get_contents($f),'BreadcrumbList')!==false) $theme_files_hit[]=str_replace(ABSPATH,'',$f);
  }
}
// Scan theme PHP for BreadcrumbList more broadly (depth 2)
$scanRoots=[get_template_directory(), get_stylesheet_directory()];
$extra=[];
foreach($scanRoots as $root){
  $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
  $n=0;
  foreach($it as $file){
    if(!$file->isFile()) continue;
    if(!preg_match('/\.(php|js)$/',$file->getFilename())) continue;
    $n++; if($n>400) break;
    $path=$file->getPathname();
    if(stripos(@file_get_contents($path),'BreadcrumbList')!==false) $extra[]=str_replace(ABSPATH,'',$path);
  }
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
// Check rendered HTML for extra BreadcrumbList via HTTP to self (localhost)
$rendered_bcs=[]; $rendered_count=0;
$url=get_permalink(303);
$ch=curl_init($url);
curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>25,CURLOPT_USERAGENT=>'pp-bc-verify',CURLOPT_SSL_VERIFYPEER=>false]);
$html=curl_exec($ch); $code=curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
if(is_string($html)){
  preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s',$html,$hm);
  foreach($hm[1] as $j){
    $d=json_decode(trim($j),true);
    if(!$d) continue;
    $nodes=isset($d['@graph'])?$d['@graph']:[$d];
    // also handle @graph nested arrays of types
    $flat=[];
    foreach($nodes as $n){
      if(!is_array($n)) continue;
      $t=$n['@type']??'';
      if(is_array($t)){ foreach($t as $tt){ $nn=$n; $nn['@type']=$tt; $flat[]=$nn; } }
      else $flat[]=$n;
    }
    foreach($flat as $n){
      if(($n['@type']??'')==='BreadcrumbList'){
        $rendered_count++;
        $els=$n['itemListElement']??[];
        $items=[]; $ok=true;
        foreach($els as $li){
          $has=isset($li['item']) && (is_string($li['item'])?$li['item']!=='':!empty($li['item']));
          // item can be object with @id
          if(!$has && isset($li['item']['@id'])) $has=true;
          if(!$has) $ok=false;
          $itemVal=$li['item']??null;
          if(is_array($itemVal)) $itemVal=$itemVal['@id']??json_encode($itemVal);
          $items[]=['position'=>$li['position']??null,'name'=>$li['name']??null,'item'=>$itemVal,'has_item'=>$has];
        }
        $rendered_bcs[]=['source'=>'rendered_html','@id'=>$n['@id']??null,'all_have_item'=>$ok,'elements'=>$items];
      }
    }
  }
}
$stale=strpos($c,'jadi-guru-les-privat-setelah-pensiun')!==false;
$cat=strpos($c,'https://pensiun.pro/category/usaha/')!==false;
$o=[
  'id'=>(int)$p->ID,'slug'=>$p->post_name,'url'=>get_permalink(303),'content_len'=>strlen($c),
  'faqpage'=>$faq,'stale_slug'=>$stale,'category_usaha_item'=>$cat,
  'content_breadcrumbs'=>$bcs,'all_listitems_have_item'=>empty($missing)&&count($bcs)>0,
  'active_seo'=>$seo,'theme'=>['name'=>$theme->get('Name'),'stylesheet'=>$theme->get_stylesheet()],
  'theme_breadcrumb_schema_files'=>array_values(array_unique(array_merge($theme_files_hit,$extra))),
  'rankmath_bc'=>$rm_bc,'yoast_bc'=>$yoast_bc,
  'rendered_http'=>$code,'rendered_breadcrumb_count'=>$rendered_count,'rendered_breadcrumbs'=>$rendered_bcs,
  'ok'=>empty($missing)&&count($bcs)>0&&!$stale&&$cat&&!$faq
];
echo "VERIFY ".json_encode($o, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
