#!/bin/sh
# verify post 305: no TOC, single-col grid, kalkulator+contrast kept
H=/home/u814079684
php -r '
$_SERVER["HTTP_HOST"]="pensiun.pro"; $_SERVER["SERVER_NAME"]="pensiun.pro";
$_SERVER["REQUEST_URI"]="/"; $_SERVER["HTTPS"]="on"; $_SERVER["SERVER_PORT"]=443;
require "/home/u814079684/domains/pensiun.pro/public_html/wp-load.php";
$p=get_post(305); $c=$p?$p->post_content:"";
$flags=[
  "id"=>305,
  "has_toc"=>(strpos($c,"Isi halaman")!==false || strpos($c,"class=\"toc\"")!==false),
  "has_300px_col"=>(strpos($c,"minmax(0, 1fr) 300px")!==false),
  "has_display_block"=>(strpos($c,".article-grid { display: block;")!==false || strpos($c,".article-grid{display:block")!==false),
  "has_kalkulator"=>(strpos($c,"id=\"kalkulator\"")!==false),
  "has_0B2D55_label"=>(strpos($c,".calc-grid label { color:#0B2D55;")!==false),
  "has_FAQPage"=>(strpos($c,"FAQPage")!==false),
  "content_sha"=>hash("sha256",$c),
];
echo "VERIFY ".json_encode($flags, JSON_UNESCAPED_SLASHES)."\n";
'
