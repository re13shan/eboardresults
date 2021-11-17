<?php

function ebr_cache_cleanup_cb($timerid, &$param)
{
  $timername = $param[0];
  $timerspec = $param[1];
  $fnlist = &$param[2];

  $locked = &$timerspec['locked'];
  if ($locked->get() == 1) {
    echo "Callback is Locked, returning.\n";
    return;
  }

  $locked->set(1);

  $i = 0;
  $expire = $timerspec['expire'] ?? 60;
  $threshold = (float) $timerspec['threshold'] ?? 1.0;
  $expstep = $timerspec['expstep'] ?? 0.5; # In each step how to decrease time
  while (true) {
    list ($code, $cfg) = $fnlist['cache']['getcfg']($timerspec);
    #echo "-----\n";
    #print_r($cfg);
    #echo "\n-----\n";
    list ($code, $cnt) = $fnlist['cache']['count']($timerspec);
    if ($code != 200) {
      echo "specified cache resource not found [timername=$timername, code=$code, cnt=$cnt]\n";
      break;
    }
    $cusage = (float) $cnt/$cfg['nrows'];
    if ($cusage <=$threshold) break;

    echo "*** timername=$timername, count=$cnt, threshold=$threshold, usage=$cusage\n";

    $timerspec['expire'] = $expire;
    #if ($timerspec['id'] == 'session') {
    #  $fnlist['session']['expire']($timerspec);
    #} else {
    #print_r($fnlist['session']['expire']($timerspec));
    print_r($fnlist['cache']['expire']($timerspec));
    #}
    $expire = (int) $expire * $expstep;
    #Swoole\Coroutine::sleep(2); # sleep only if the cache is not cleaned up in first shot
  }

  $locked->set(0);
}

function convert_csv($file,$type,$board,$unnamed)
{
  if(!file_exists($file))  return false;
  $file = fopen($file, 'r');
  while (($row = fgetcsv($file,4096,'|')) !== FALSE) {
    switch($type){
      case 'dist':        
        $par1 = $row[0];
	$par2 = $row[1];
	$par3 = $row[2];
        break;

      case 'thana':        
        $par1 = $row[0];
	$par2 = $row[1];
	$par3 = $row[2];
        break;


      case 'board':
        $par1 = "$board";
        $par2 = $row[0];
	$par3 = $row[1];
        break;

    }
    //if($unnamed == true) $ret[$par][999] = "Unnamed";
    $ret[$par1][$par2] = $par3;
  }
  fclose($file);
  return $ret;
}


function _get_dob($dob)
{
  $dob = trim($dob);
  /*
   * 050197 {05-01-1997} {len=6}
   * 50197 {05-01-1997} {len=5}
   * 28071999 {28-07-1999} {len=8}
   * mm/dd/yyyy {BOU format}
   */
  /*
   * check if BOU */
  $isbou = false;
  if (strpos($dob, '/') !== false) $isbou = true;

  $len = strlen($dob);

  if ($isbou) { /* mm/dd/yyyy */
    $mdy = preg_split("/\//", $dob);
    $m = $mdy[0];
    $d = $mdy[1];
    $y = $mdy[2];
  } else {
    if ($len == 5 || $len == 6) {
      $y = substr($dob, -2);
      $m = substr($dob, -4, 2);
      if ($len == 6) { /* ddmmyy */
	$d = substr($dob, 0, 2);
      } else { /* dmmyy */
	$d = substr($dob, 0, 1);
      }
    } else { /* len maybe 7 or more, yyyy format */
      $y = substr($dob, -4);
      #$y = substr($y, -2); # Just take last two digits
      $m = substr($dob, -6, 2);
      if ($len == 7) { /* dmmyyyy */
	$d = substr($dob, 0, 1);
      } else { /* ddmmyyyy */
	$d = substr($dob, 0, 2);
      }
    }
  }

  if ($y < 70) $y = 2000 + $y;
  else if ($y > 70 && $y < 100) $y = 1900 + $y;

  $dmy = [ $d, $m, $y ];

  for ($i = 0; $i < count($dmy); $i++) {
    #$dmy[$i] = preg_replace("/^0/", "", $dmy[$i]);
    $dmy[$i] = sprintf("%02d", $dmy[$i]);
  }

  $dmy = implode("-", $dmy);
  #_dfile($dmy);

  return $dmy;
}

/*
function get_code_names ($dbh, $table, $search_column, $result_column, $key, $not_found_text, $extra='', $limit='LIMIT 1')
{
  $wc = "";
  if (is_array($search_column)) {
    # $key is ignored, $search_column is a associative array specifying key vand value
    foreach ($search_column as $k => $v) {
      $vs[] = "`$k`='$v'";
    }
    $wc = implode(" AND ", $vs);
  } else {
    $wc = "`$search_column`='$key'";
  }
  $query = "SELECT $result_column FROM `$table` WHERE $wc $extra $limit";
  #file_put_contents(sys_get_temp_dir() . "/xyz.txt", $query . "\n", FILE_APPEND);
  #$result = mysqli_query($dbh, $query);
  $result = $dbh->query($query);

  $ret = "$not_found_text $key";
  if ($result && $result->num_rows > 0) {
    if ($result_column != "*") {
      $row = $result->fetch_array();
      $ret = stripslashes($row[$result_column]);
    } else {
      $ret = $result->fetch_object();
    }
    $result->free($result); // free resources
  }
  return $ret;
}
*/

/*
function get_sub_tbl($filename, $codes, $type="json")
{
  $final = [];


  $filename = basename($filename);
  if (substr($filename, -5) != ".json") return $final;

  if (file_exists($filename)) {
    $all_codes = json_decode(file_get_contents($filename));

    foreach ($all_codes as $obj) {
      if (isset($obj->SUB_CODE)) {
	$code = $obj->SUB_CODE;
      } else if (isset($obj->CODE)) {
	$code = $obj->CODE;
      } else {
	$code = "";
      }
      if ($code && in_array($code, $codes)) {
	$final[] = $obj;
      }
    }
  }

  $resp = false;
  if ($type == "json" || $type == "") {
    $resp = json_encode($final);
  } else if ($type == "object") {
    $resp = $final;
  } else {
    # tsv format
    $resp = "SUB_CODE\tSUB_NAME\n";
    foreach ($final as $obj) {
      $resp .= $obj->SUB_CODE . "\t" . $obj->SUB_NAME . "\n";
    }
  }

  return $resp;
}
*/

function get_sub_tbl_json($all_codes, $codes)
{
  $final = [];

  foreach ($all_codes as $obj) {
    if (isset($obj->SUB_CODE)) {
      $code = $obj->SUB_CODE;
    } else if (isset($obj->CODE)) {
      $code = $obj->CODE;
    } else {
      $code = "";
    }
    if ($code != "" && in_array($code, $codes)) {
      $final[] = $obj;
    }
  }

  return $final;
  #$resp = json_encode($final);
}

function get_codes_from_result($result)
{
  $all_codes = [];

  $delims = ["+", "-"];
  $fs = explode(",", trim($result));
  foreach ($fs as $codemark) {
    $cm = explode(":", $codemark);
    if (count($cm) < 2) continue; # must have code:value format, otherwise ignore
    $code = $cm[0];
    # code might have '-' or '+' in it
    $codes = [$code];
    foreach ($delims as $delim) {
      if (strpos($code, $delim)) {
	$codes = explode($delim, $code);
	break;
      }
    }
    foreach ($codes as $code) {
      $code = trim($code);
      if ($code != "") {
       	$all_codes[] = $code;
      }
    }
  }
  return $all_codes;
}

# get closest year for subject file loading
function get_closest_sub_year($number, $array)
{
  $ret = 0;

  if (!is_array($array) || count($array) == 0) return $ret;

  # check if exact match found
  if (in_array($number, $array)) return $number;

  sort($array, SORT_NUMERIC);
  $ret = $array[0]; # default to first element
  foreach ($array as $a) {
    if ($number > $ret) {
      if ($number < $a) break;
      $ret = $a;
    }
  }
  return $ret;
}

# get subject years
function get_sub_years($exam, $board)
{
  $fpat = "${exam}_sub_${board}_";
  $ext = ".json";
  $files = glob("${fpat}*${ext}");
  #print_r($files);
  $years = str_replace($ext, "", str_replace($fpat, "", $files));
  #print_r($years);

  return $years;
}


# $dob = dd mm yyyy
function dob2txt($dob)
{
  $parts = preg_split("/[\/\.-]/", $dob);
  #print_r($parts);
  #return true;

  $dd = preg_replace("/^0?/", "", $parts[0]);
  $mm = preg_replace("/^0?/", "", $parts[1]);
  $yyyy = $parts[2];

  $mon_map = [
    "None", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"
  ];
  $day_map = [
    "Zeroth", "First", "Second", "Third", "Fourth", "Fifth", "Sixth", "Seventh",
    "Eighth", "Ninth", "Tenth", "Eleventh", "Twelfth", "Thirteenth",
    "Fourteenth", "Fifteenth", "Sixteenth", "Seventeenth", "Eighteenth",
    "Nineteenth", "Twentieth", "Twenty-first", "Twenty-second", "Twenty-third",
    "Twenty-fourth", "Twenty-fifth", "Twenty-sixth", "Twenty-seventh",
    "Twenty-eighth", "Twenty-ninth", "Thirtieth", "Thirty-first"
  ];

  $year = "";
  $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
  if ($yyyy >= 2000) {
    $year = $f->format($yyyy);
  } else {
    $yy_0 = substr($yyyy, 0, 2);
    $year .= $f->format($yy_0) . " Thousand and ";
    $yy_1 = substr($yyyy, 2, 2);
    $year .= $f->format($yy_1);
  }

  #$year = str_replace("-", " ", $year);

  $day = $day_map[$dd];
  $mon = $mon_map[$mm];
  $year = ucwords($year);

  $dtxt = "${day} ${mon}, ${year}";

  return $dtxt;
}


# doesn't handle any fancy csv file, just works for my needs
function csv2obj($csv, $delim=",")
{
  $objs = [];
  $lines = explode("\n", $csv);
  $head = array_shift($lines);
  $hcols = explode($delim, $head);
  foreach ($lines as $line) {
    $vals = explode($delim, $line);
    $obj = new stdClass();
    $i = 0;
    foreach ($vals as $v) {
      $col = $hcols[$i];
      $obj->$col = $v;
      $i++;
    }
    $objs[] = $obj;
  }

  return $objs;
}

function fix_grade_display($display)
{
  # Comilla SSC 2018 has ltrgd_ca in format [ 154=A+,156=A+ ]
  # Handling above in general
  # Rule: if there is '=' and no ':', then explode with ',' and for each part replace first '=' sign with ':', then implode with ','
  # Hopefully this should not break other ltrgd/marks_grd pattern

  $delim = ','; # marks/grade delimiter
  if ($display != '') {
    $search = '=';
    $searchlen = strlen($search);
    $replace = ':';
    if (strpos($display, $search) !== false && strpos($display, $replace) === false) {
      $parts = explode($delim, $display);
      $cnt = count($parts);
      for ($i = 0; $i < $cnt; $i++) {
	$pos = strpos($parts[$i], $search); # find position of '=' from beginning of the grade part
	if ($pos !== false) {
	  $parts[$i] = substr_replace($parts[$i], $replace, $pos, $searchlen);
	}
      }
      $display = implode($delim, $parts);
    }
  }
  return $display;
}

/*
function result($status, $msg, $extra=false)
{
  #global $__stat_url; # getres_impl.php assigns it
  $resp = new stdClass();
  $resp->status = $status;
  $resp->msg = $msg;
  #if (isset($__stat_url) && $__stat_url != "") $resp->stat_url = $__stat_url;
  $resp->extra = $extra;
  return $resp;
  #echo json_encode($resp);
  #die();
}
*/


#$dob = "25-07-1998";
#echo dob2txt($dob);

function ebr_app_init($env=null)
{
  $app = new stdClass();

  #$app->suffix = '_dev';
  $app->suffix = '';

  $app->dbhost = 'localhost';
  $app->dbport = 3314;
  $app->dbuser ='';
  $app->dbpass ='';
  $app->dbname ='';
  $app->dbprefix = '';
  $app->dbtype = "mysql";

  $app->header_map_file = "/app/header_map.conf";
  $app->footer_map_file = "/app/footer_map.conf";
  $app->menu_map_file = "/app/menu_map.conf";
  #$app->header_file = "header.html";
  $app->header_file = "/app/header" . $app->suffix . ".html";
  $app->header_title = "WEB BASED RESULT PUBLICATION SYSTEM FOR EDUCATION BOARDS OF BANGLADESH"; # header_map.conf is used instead
  //$app->header_subtitle = "School Admission 2016";
  $app->header_subtitle = "JSC/SSC/DAKHIL/HSC/ALIM AND EQUIVALENT EXAMINATION"; # header_map.conf is used instead
  $app->header_logo = "/app/images/bd_logo.png";

  $app->footer_file = "/app/footer" . $app->suffix . ".html";
  $app->footer_powered_by = "Nixtec Systems";
  $app->footer_developed_by = "Nixtec Systems <img src=\"/app/images/dev_logo.gif\" alt=\"Nixtec Systems\">";
  $app->footer_owned_by = "Ministry of Education, Bangladesh";

  #$app->root = $_SERVER['DOCUMENT_ROOT'];
  $app->root = "/vh/g/nixtec/vhosts/eboardresults.com/www";
  $app->dir = "/vh/g/nixtec/vhosts/eboardresults.com/www/app/stud";
  $app->dprefix = "/cdn/ext"; # relative to $app->root
  $app->cdn = '//cdn.nixtecsys.com/eb';
  $app->usecdn = false; # set to true to globally use CDN (when possible/configured)

  #$app->css[] = ['name' => 'bootstrap', 'ver' => '-3.3.7-dist', 'file' => 'css/bootstrap.min.css', 'usecdn' => $app->usecdn]; # true == use cdn
  #$app->css[] = ['name' => 'bootstrap', 'ver' => '-3.3.7-dist', 'file' => 'css/bootstrap.min.css'];
  #$app->css[] = ['name' => 'bootstrap3-dialog', 'ver' => '', 'file' => 'bootstrap-dialog.min.css'];

  # Internal
  #$app->js[] = ['name' => 'jquery', 'ver' => '', 'file' => 'jquery-3.2.1.min.js', 'usecdn' => $app->usecdn]; # true == use cdn

  
  # External
  #$app->css[] = ['name' => 'bootstrap', 'external' => true, 'url' => 'https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css'];
  $app->css[] = ['name' => 'bootstrap', 'external' => true, 'url' => 'https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.4.0/css/bootstrap.min.css'];
  #$app->js[] = ['name' => 'jquery', 'external' => true, 'url' => 'https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js'];
  $app->js[] = ['name' => 'jquery', 'external' => true, 'url' => 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.0/jquery.min.js'];
  #$app->js[] = ['name' => 'bootstrap', 'external' => true, 'url' => 'https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js'];
  $app->js[] = ['name' => 'bootstrap', 'external' => true, 'url' => 'https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.4.0/js/bootstrap.min.js'];

  #$app->js[] = ['name' => 'jquery', 'ver' => '', 'file' => 'jquery-3.2.1.min.js'];
  #$app->js[] = ['name' => 'bootstrap', 'ver' => '-3.3.7-dist', 'file' => 'js/bootstrap.min.js', 'usecdn' => $app->usecdn]; # true == use cdn
  #$app->js[] = ['name' => 'bootstrap', 'ver' => '-3.3.7-dist', 'file' => 'js/bootstrap.min.js'];
  #$app->js[] = ['name' => 'bootstrap3-dialog', 'ver' => '', 'file' => 'bootstrap-dialog.min.js'];


  # Invoke custom result fetch routine based on following 'exam', 'year', 'type' values (this will help us serve result during high load)
  #$use_session = true; # We need session here just for Testimonial Service, during result we make it off
  $use_session = false; # We need session here just for Testimonial Service, during result we make it off
  #$use_session = false; # We need session here just for Testimonial Service, during result we make it off
  #$captcha_svc_url = '/cdn/svc/captcha/captcha.php';
  $captcha_svc_url = 'captcha';
  #$result_svc_url = '/app/stud/getres.php';
  $result_svc_url = 'getres';
  $result_svc_method = 'post';
  #$pdf_dl_url = '/app/stud/pdl.php';
  $pdf_dl_url = 'pdl';
  #$dist_list_url = '/app/stud/dlist.php';
  $dist_list_url = 'list?id=dlist';
  #$cent_list_url = '/app/stud/clist.php';
  $cent_list_url = 'list?id=clist';
  $btree_url = 'list?id=btree';

  $app->allow_proxy = true; # should we be gentle on proxy (proxy.amar.life, etc.)?



  # if any customisation of the above is required, please do in extsvc.php
  #require_once(__DIR__ . '/stud/extsvc.php');
  #if (isset($extsvc)) $app->extsvc = $extsvc;

# Enable Statistics
$dostat = 0;
#$__stat_baseurl = "stat.php?"; # need to have '?'
#$__stat_baseurl = "/c/stat/add?"; # need to have '?'

# Slow but Full Fleged PDF Viewer, uses mostly local resources (not good solution for loaded case, result, etc.)
$pdfviewer_file = "/app/inst/pjs-latest/web/viewer.html"; # uses file=$path (uses local copy, not CDN)
# fast viewer (use during result)
# view all pages, no toolbar
#$pdfviewer_file = "../inst/pv/simpleviewer-cdn.html"; # it uses hard-coded /app/stud/pdl.php, file=$path is ignored (uses CDN)
#$pdfviewer_file = "../inst/pv/simpleviewer-dist.html"; # it uses hard-coded /app/stud/pdl.php, file=$path is ignored (uses local copy)


# Use Cool Captcha
#$captcha_svc_url = '/cdn/svc/captcha_cool/captcha.php';
# Custom (Static,Lightweight) Captcha for Result Load Handling
$captcha_svc_url = 'captcha';


#$extsvc = ['exam' => 'ssc', 'year' => 2018, 'type' => 1, 'url' => 'getres.php', 'method' => 'post', 'captcha' => '']; # captcha may be a URL as well, if empty, no captcha is required


  $app->custom_css_code = "";
  $app->custom_js_vars = "\n";
  $app->custom_html_code = "";

  # custom
  # javascript code injected
  #
  # configuration for result when Grading System was introduced (to show 'Grade' or 'Marks' in Table Title
  $grd_start_config = [
    'ssc' => 2001,
    'hsc' => 2003,
    'jsc' => 2010
  ];

  $app->custom_js_vars .= "var grd_start_config = " . json_encode($grd_start_config) . ";\n";
  $app->custom_js_vars .= "var captcha_svc_url = '$captcha_svc_url';\n";
  $app->custom_js_vars .= "var result_svc_url = '$result_svc_url';\n";
  $app->custom_js_vars .= "var result_svc_method = '$result_svc_method';\n";
  $app->custom_js_vars .= "var pdf_dl_url = '$pdf_dl_url';\n";
  $app->custom_js_vars .= "var google_charts_url = 'https://www.gstatic.com/charts/loader.js';\n";
  $app->custom_js_vars .= "var dist_list_url = '$dist_list_url';\n";
  $app->custom_js_vars .= "var cent_list_url = '$cent_list_url';\n";
  $app->custom_js_vars .= "var testimonial_css_url = '/app/stud/allcss/testimonial.css';\n";
  $app->custom_js_vars .= "var eiin_finder_tree_url = '/app/stud/btree.html';\n";
  $app->custom_js_vars .= "var eiin_finder_tbl_url = '/app/stud/btbl.html';\n";
  $app->custom_js_vars .= "var btree_url = '" . rawurlencode($btree_url) . "';\n";
  if (isset($app->extsvc)) {
    $app->custom_js_vars .= "var extsvc = " . json_encode($app->extsvc) . ";\n";
    //$app->custom_js_vars .= "console.log(extsvc);\n";
  }

  # Default values (global scope)
  $app->custom_js_vars .= "var captcha_url = captcha_svc_url;\n";
  $app->custom_js_vars .= "var result_url = result_svc_url;\n";
  $app->custom_js_vars .= "var result_method = result_svc_method;\n";


  # twitter follow icon
  $app->custom_css_code .= "#twitter-follow { border: 0;position: fixed;  top: 150px; right:0;}";

  # show follow on twitter icon
  $app->custom_html_code .= '<div class="social">'; # social open
  $app->custom_html_code .= '<div id="twitter-follow"> <a href="https://twitter.com/nixtecsystems" target="_blank"><img width=80 height=80 src="/cdn/common/img/Twitter_Logo_Blue.svg" alt="Follow us on Twitter" /></a> </div>';
  $app->custom_html_code .= '</div>'; # social closed
  $app->custom_html_code .= "\n";

  # custom_html_code comes at last
  #if ($_SERVER['REMOTE_ADDR'] == '27.147.203.17') {
  # experimental code here
  #}
  $app->custom_html_code .= '<script type="text/javascript">$(window).scroll(function() {if ($(this).scrollTop() > 1) { if ($(".social").is(":visible")) $(".social").fadeOut(); }  else if (!$(".social").is(":visible")) $(".social").fadeIn(); });</script>' . "\n";

  return $app;
}


#$db_impl = "db." . $app->dbtype . ".php";
#require_once($db_impl);

function process_tags($app, &$search, &$replace)
{
  $search[] = "%__style_sheet__%";
  $css = "";
  #echo dirname(__FILE__) . "/" . $app->style_sheet;
  if (isset($app->style_sheet)) {
    $path = $app->style_sheet[0] == '/' ? $app->root : $app->dir . "/";
    $path .= $app->style_sheet;
    if (($mtime = filemtime($path)) !== FALSE) { # Saving extra call to file_exists(). filemtime() will already check for this
      $css = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $app->style_sheet . "?t=$mtime" . "\" />\n";
    }
  }
  $replace[] = $css;

  $search[] = "%__combo_sheet__%";
  $css = "";
  foreach ($app->css as $cinfo) {
    if (isset($cinfo['external']) && $cinfo['external'] == true) {
      $href = $cinfo['url'];
    } else {
      $file = $app->dprefix . "/" . substr($cinfo['name'], 0, 1) . "/" . $cinfo['name'] . $cinfo['ver'] . "/" . $cinfo['file'];
      $href = $file;
      if (isset($cinfo['ts']) && $cinfo['ts'] == true) $href .= "?t=" . filemtime($app->root . $file);

      if (isset($cinfo['usecdn']) && $cinfo['usecdn'] == true) {
	$href = $app->cdn . $href;
      }
    }
    $css .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $href . "\" />\n";
  }
  $replace[] = $css;


  $search[] = "%__ready_script__%";
  $js = "";
  if (isset($app->ready_script)) {
    $path = $app->ready_script[0] == '/' ? $app->root : $app->dir . "/";
    $path .= $app->ready_script;
    if (($mtime = filemtime($path)) !== FALSE) { # Saving extra call to file_exists(). filemtime() will already check for this
      $js = "<script src=\"" . $app->ready_script . "?t=$mtime" . "\"></script>\n";
    }
  }
  $replace[] = $js;

  $search[] = "%__combo_script__%";
  $js = "";
  foreach ($app->js as $jinfo) {
    if (isset($jinfo['external']) && $jinfo['external'] == true) {
      $src = $jinfo['url'];
    } else {
      $file = $app->dprefix . "/" . substr($jinfo['name'], 0, 1) . "/" . $jinfo['name'] . $jinfo['ver'] . "/" . $jinfo['file'];
      $src = $file;
      if (isset($jinfo['ts']) && $jinfo['ts'] == true) $src .= "?t=" . filemtime($app->root . $file);

      if (isset($jinfo['usecdn']) && $jinfo['usecdn'] == true) {
	$src = $app->cdn . $src;
      }
    }
    $js .= "<script src=\"" . $src . "\"" . (isset($jinfo['async']) && $jinfo['async'] == true ? " async defer" : "") . "></script>\n";
  }
  $replace[] = $js;
}


function app_get_header($app)
{
  $path = $app->header_file[0] == '/' ? $app->root : $app->dir . "/";
  $path .= $app->header_file;
  $header = file_get_contents($path);
  $search[] = "%__header_title__%";
  $replace[] = $app->header_title;
  $search[] = "%__header_subtitle__%";
  $replace[] = $app->header_subtitle;
  $search[] = "%__header_logo__%";
  $replace[] = $app->header_logo;

  $search[] = "%__custom_css_code__%";
  $replace[] = $app->custom_css_code;

  process_tags($app, $search, $replace);

  /*
   * Meta tag is not that effective. Page Content is Most Imporatant for Search Engines
   */
  /*
  if (!isset($app->all_years)) {
    if (file_exists("allyears")) $app->all_years = "Year " . file_get_contents("allyears");
    else $app->all_years = "";
  }
  $search[] = "%__all_years__%";
  $replace[] = $app->all_years;
   */

  return str_replace($search, $replace, $header);
}

function app_get_footer($app)
{
  $path = $app->footer_file[0] == '/' ? $app->root : $app->dir . "/";
  $path .= $app->footer_file;
  $footer = file_get_contents($path);
  $search[] = "%__footer_powered_by__%";
  $replace[] = $app->footer_powered_by;
  $search[] = "%__footer_developed_by__%";
  $replace[] = $app->footer_developed_by;
  $search[] = "%__footer_owned_by__%";
  $replace[] = $app->footer_owned_by;

  $search[] = "%__custom_js_vars__%";
  $replace[] = $app->custom_js_vars;

  $search[] = "%__custom_html_code__%";
  $replace[] = $app->custom_html_code;


  process_tags($app, $search, $replace);

  return str_replace($search, $replace, $footer);
}


function gen_form($obj,$form_id,$form_action)
{
  $ret  = "";

  $forms ="<form role=\"form\"  id=\"$form_id\" action=\"$form_action\" method=\"post\" enctype=\"multipart/form-data\">";
  $ret .= $forms;
  $count = 0;
  foreach($obj as $row){
    $lst = count($row)-1;
    $count++;
    if (isset($row[0]->fieldset_start)) { 
      $ret .= "<div id=\"field_" . $count . "\">"; 
      if(isset($row[0]->text_color))  $text_color=$row[0]->text_color; 
      else $text_color= '';
      $ret .="<fieldset class=\"well addr-fieldset\"><legend class=\"addr-legend\" ><label class=\"$text_color\">" . $row[0]->fieldset_start . "</label></legend>";
    }

    if(isset($row[0]->div_start)){
      $ret .= "<div id=\"". $row[0]->id ."\">";
    }
#$ret .="<div class=\"row\" id=\"col_" . (isset($col->name)?$col->name:'') . "\">";
    $ret .="<div class=\"row\" id=\"col_" . $count . "\">";

    foreach($row as $col){
      $ret .="<div id=\"row_" . preg_replace("/[\[\]]+/", "", isset($col->name)?$col->name:'') . "\" " . (isset($col->sdisplay)? "style=\"display: " . $col->sdisplay . "\"" : "") .  ">";
      if(isset($col->class_label)){
	$ret .="<div class=\"form-group " . $col->class_label . "\">\n";
	$ret .= "<label>" . $col->label . "</label>";
	$ret .= "</div>";
      }
      if(isset($col->class_obj)){
	$ret .="<div class=\"form-group " . $col->class_obj . "\">\n";
	$ret .=gen_input($col);
	$ret .="</div>";
      }
      $ret .="</div>";
    }
    $ret .="</div>";
    if (isset($row[$lst]->fieldset_end)) {
      $ret .= "</div>";
      $ret .="</fieldset>";
#echo "</div>";
    }
    if (isset($row[$lst]->div_end)){
      $ret .="</div>";
    }
  }
  $ret .="</form>";
  return $ret;
}




/* mother wrapper function */
function gen_input($obj)
{
  $ret = '';

  switch ($obj->type) {
  case 'text':
  case 'number':
  case 'password':
  case 'email':
    $ret = gen_text($obj);
    break;
  case 'textarea':
    $ret = gen_textarea($obj);
    break;
  case 'select':
    $ret = gen_select($obj);
    break;
  case 'radio':
    $ret = gen_radio($obj);
    #if (isset($obj->down) && $obj->down == true) $ret = gen_radio_down($obj);
    #else $ret = gen_radio($obj);
    break;
  case 'submit':
    $ret=gen_submit($obj);
    break;
  case 'button':
    $ret=gen_button($obj);
    break;
  case 'checkbox':
    $ret=gen_check($obj);
    break;

  case 'hidden':
    $ret = gen_hidden($obj);
    break;

  default:
    break;
  }

  return $ret;
}


function gen_text($obj)
{
  $type = $obj->type;
  $name = $obj->name;
  if(isset($obj->id))  $id = $obj->id; else $id = $name;
  if (isset($obj->class)) $class = $obj->class; else $class = "";
  if (isset($obj->value)) $value = $obj->value; else $value = "";
  
  if (isset($obj->disabled) && $obj->disabled) $disabled = "disabled";
  else $disabled = "";
  if(isset($obj->readonly) && $obj->readonly) $readonly= "readonly";
  else $readonly="";
  if(isset($obj->step) && $obj->step  ) $step = "0.01";
  else $step = "";
  
  $tooltip = (isset($obj->tooltip) && $obj->tooltip == true)  ? "data-toggle=\"tooltip\"" : '';
  $tool_place = isset($obj->tool_place) ? "data-placement=\"$obj->tool_place\"" :'';
  $tool_title = isset($obj->tool_title) ? "title=\"$obj->tool_title\"" :'';

  
  if(isset($obj->autofocus) && $obj->autofocus) $autofocus = "autofocus"; else $autofocus = "";
  if (isset($obj->required) && $obj->required == false) $required = ""; else $required="required";
  if (isset($obj->autocomplete)) $autocomplete = "autocomplete=\"$obj->autocomplete\""; else $autocomplete = "";
  
  $placeholder= (isset($obj->placeholder)? $obj->placeholder : "");
  return "<input class=\"form-control $class\" type=\"$type\" name=\"$name\" value=\"$value\" placeholder=\"$placeholder\" id=\"$id\" $tooltip $tool_place $tool_title $autofocus $required $disabled $readonly $autocomplete step=\"$step\">";
}

function gen_textarea($obj)
{
  $type = $obj->type;
  $name = $obj->name;
  if (isset($obj->value)) $value = $obj->value; else $value = "";
  if(isset($obj->id))  $id = $obj->id; else $id = $name;
  if (isset($obj->rows)) $rows = "rows=" . $obj->rows; else $rows = "";
  if (isset($obj->disabled) && $obj->disabled) $disabled = "disabled";
  else $disabled = "";
  if (isset($obj->required) && $obj->required == false) $required = ""; else $required = "required";
  $placeholder= (isset($obj->placeholder)? $obj->placeholder : "");
  return "<textarea class=\"form-control\" type=\"$type\" name=\"$name\" placeholder=\"$placeholder\" id=\"$id\" $rows autofocus $required $disabled>$value</textarea>";
}


function gen_select($obj)
{
  $type = $obj->type;
  $name = $obj->name;
  if(isset($obj->id))  $id = $obj->id; else $id = $name;
  if (isset($obj->placeholder)) $placeholder = $obj->placeholder;
  else $placeholder = "Choose One";
  

  if (isset($obj->empty_text)) $empty_text = $obj->empty_text; else $empty_text = "Choose One";
  
  $opts = $obj->options;
  if (isset($obj->required) && $obj->required == false) $required = ""; else $required="required";
  $ret = "<select id=\"$id\" name=\"$name\" class=\"form-control\" $required>";
  if(isset($obj->label_text) && $obj->label_text == false) $ret .= '';
  else $ret .= "<option value=\"\">$placeholder</option>";
  
  if (isset($opts) && is_array($opts)) {
    foreach ($opts as $opt) {
      if (!isset($opt['value']) || $opt['value'] === "") continue;
      $value = $opt['value'];
      $display = $opt['display'];
      if (isset($opt['selected']) && $opt['selected'] == true) $selected = "selected"; else $selected = "";
      $ret .= "<option value=\"$value\" $selected>$display</option>";
    }
  }
  $ret .= "</select>";

  return $ret;
}

/* inline radio button */
function gen_radio($obj){
  $type =$obj->type;
  $name=$obj->name;
  $opts=$obj->options;
  if(isset($obj->id))  $id = $obj->id; else $id = $name;
  $class = "radio-inline";
  if (isset($obj->down)) $class = "radio";
  $ret = "";
  foreach ($opts as $opt) {
    if (!isset($opt['value']) || $opt['value'] === "") continue;
    $value=$opt['value'];
    $display=$opt['display'];
    if (isset($opt['checked']) && $opt['checked'] == true) $checked = "checked"; else $checked = "";
    $ret .= "<label class=\"$class\"><input type=\"$type\" id=\"$id\" name=\"$name\" value=\"$value\" $checked>   $display   </label>";
    #echo $ret;
  }

  return $ret;
}


function gen_check($obj)
{
  $type =$obj->type;
  $name=$obj->name;
  if(isset($obj->options))  $opts=$obj->options;
  else $opts='';
  $class = "checkbox-inline";
  if (isset($obj->down)) $class = "checkbox";
  $ret = "";

 if($opts == ''){
    $value = '';
    $id = $name;
    $ret .= "<label class=\"$class\"><input type=\"$type\" $id name=\"$name\" value=\"$value\" ></label>";
    return $ret;
    return false;
  }

  foreach ($opts as $opt) {
    if (!isset($opt['value']) || $opt['value'] == "") continue;
    $value=$opt['value'];
    $display=$opt['display'];
    if (isset($opt['checked'])) $checked = $opt['checked']; else $checked = false;
    if (isset($opt['id'])) $id = "id=\"" . $opt['id'] . "\""; else $id = "";
    #$ret .= "<div class=\"$type\"><label><input type=\"$type\" name=\"$name\" value=\"$value\" " . ($checked? "checked" : "") . ">$display</label></div>";
    $ret .= "<label class=\"$class\"><input type=\"$type\" $id name=\"$name\" value=\"$value\" " . ($checked? "checked" : "") . ">$display</label>";
  }
  return $ret;
}
  

function gen_submit($obj)
{
  $type = $obj->type;
  $name = $obj->name;
  $class=$obj->class;
  if (isset($obj->value)) $value = $obj->value; else $value = "";
  $id=$name;
  $data_toggle = isset($obj->data_toggle) ? "data-toggle=\"$obj->data_toggle\"": '';
  $data_target = isset($obj->data_target) ? "data-target=\"$obj->data_target\"": '';
  return "<input class=\"$class\" type=\"$type\" name=\"$name\" id=\"$id\" value=\"$value\" $data_target $data_toggle >";
}

function gen_button($obj)
{
  $type = $obj->type;
  $name = $obj->name;
  if (isset($obj->value)) $value = $obj->value; else $value = "";
  $class=$obj->class;
  $id=$name;
  if (isset($obj->onclick)) $onclick = "onclick=\"" . $obj->onclick . "\""; else $onclick = "";
  return "<button class=\"$class\" type=\"$type\" name=\"$name\" id=\"$id\" $onclick>$value</button>";
}

function ebr_render_app($env=null)
{

  $appver = "2";
  $app = ebr_app_init();
  if ($env == null) {
    $env = [];
    $env['request'] = [];
    $env['defs'] = [];
  }

  $_REQUEST = &$env['request'];
  $defs = &$env['defs'];

  $output = "";

  $app->style_sheet = "/app/style.min.css";
  $app->ready_script = "/app/stud/ready-v{$appver}.min.js"; # if version upgrades, update the '-v2' to corresponding version


  # Internal
  #$app->css[] = ['name' => 'bootstrap3-dialog', 'ver' => '', 'file' => 'bootstrap-dialog.min.css', 'usecdn' => $app->usecdn]; # use globally configured value
  #$app->js[] = ['name' => 'bootstrap3-dialog', 'ver' => '', 'file' => 'bootstrap-dialog.min.js', 'usecdn' => $app->usecdn]; # use globally configured value

  # External
  $app->css[] = ['name' => 'bootstrap3-dialog', 'external' => true, 'url' => 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap3-dialog/1.35.4/css/bootstrap-dialog.min.css']; # use globally configured value
  $app->js[] = ['name' => 'bootstrap3-dialog', 'external' => true, 'url' => 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap3-dialog/1.35.4/js/bootstrap-dialog.min.js']; # use globally configured value

  #$app->js[] = ['name' => 'google-charts', 'ver' => 'latest', 'external' => true, 'async' => true, 'url' => 'https://www.gstatic.com/charts/loader.js'];

  #$app->combo_script = "combo.min.js";

  # Manipulate Header
  #$header_items = app_get_map_items($app, "header_map_file");
  #if ($header_items) {
  #$hdr = $header_items[0];
  $app->header_title = "WEB BASED RESULT PUBLICATION SYSTEM FOR EDUCATION BOARDS";
  $app->header_subtitle = "JSC/JDC/SSC/DAKHIL/HSC/ALIM AND EQUIVALENT EXAMINATION";
  $app->header_logo = "/app/images/bd_logo.png";
  #}

  $output = app_get_header($app);




  $form_id = 'form';
  $form_action = "";
  $form_class = "col-md-12";
  $page_title = "";
  $panel_heading = "Please provide information for result (<a href=\"/app/doc/\" target=\"_blank\"><font color=red>User Guide</font></a>) (<a href=\"/\"><font color=blue>Home</font></a>) (<a href=\"/app/stud/stat-v2.html?url=/v{$appver}/stat\"><font color=blue>Statistics</font></a>)";



  $filter_exam = false;
  if (isset($_REQUEST['exam']) && isset($defs['exam_map'][$_REQUEST['exam']])) {
    $filter_exam = $_REQUEST['exam'];
  }
  $input_obj = new stdClass();
  $input_obj->label = "Examination";
  $input_obj->name = "exam";
  #$input_obj->label_text = false;
  $input_obj->type = "select";
  foreach($defs['exam_map'] as $k=>$v){
    if ($filter_exam && $filter_exam != $k) continue;
    $input_obj->options[] = ["value" => $k, "display" => $v, "selected" => $filter_exam]; # if filter_* is true, then it should be selected by default
    #$input_obj->options[] = ["value" => $k, "display" => $v];
  }
  $input_obj->class_label = "col-md-5";
  $input_obj->class_obj = "col-md-7";
  $rows[] = [$input_obj];



  $filter_year = false;
  if (isset($_REQUEST['year']) && isset($defs['year_map'][$_REQUEST['year']])) {
    $filter_year = $_REQUEST['year'];
  }
  $input_obj = new stdClass();
  $input_obj->label = "Year";
  $input_obj->name = "year";
  #$input_obj->label_text = false;
  $input_obj->type = "select";
  foreach($defs['year_map'] as $k=>$v){
    if ($filter_year && $filter_year != $k) continue;
    $input_obj->options[] = ["value" => $k, "display" => $v, "selected" => $filter_year]; # if filter_* is true, then it should be selected by default
    #$input_obj->options[] = ["value" => $k, "display" => $v];
  }
  $input_obj->class_label = "col-md-5";
  $input_obj->class_obj = "col-md-7";
  $rows[] = [$input_obj];


  $filter_board = false;
  if (isset($_REQUEST['board']) && isset($defs['board_map'][$_REQUEST['board']])) {
    $filter_board = $_REQUEST['board'];
  }
  $input_obj = new stdClass();
  $input_obj->label = "Board";
  $input_obj->name = "board";
  #$input_obj->label_text = false;
  $input_obj->type = "select";
  foreach($defs['board_map'] as $k=>$v){
    if ($filter_board && $filter_board != $k) continue;
    $input_obj->options[] = ["value" => $k, "display" => $v, "selected" => $filter_board]; # if filter_* is true, then it should be selected by default
    #$input_obj->options[] = ["value" => $k, "display" => $v];
  }
  $input_obj->class_label = "col-md-5";
  $input_obj->class_obj = "col-md-7";
  $rows[] = [$input_obj];

  $filter_rtype = false;
  if (isset($_REQUEST['rtype']) && isset($defs['result_type'][$_REQUEST['rtype']])) {
    $filter_rtype = $_REQUEST['rtype'];
  }
  $input_obj = new stdClass();
  $input_obj->label = "<font color=red>Result Type</font>";
  $input_obj->name = "result_type";
  $input_obj->type = "select";
  foreach($defs['result_type'] as $k=>$v){
    if ($filter_rtype && $filter_rtype != $k) continue;
    $input_obj->options[] = ["value" => $k, "display" => $v]; # input fields are activated on the event of selecting an option. so we're not making it default
    #$input_obj->options[] = ["value" => $k, "display" => $v, "selected" => $filter_rtype]; # if filter_* is true, then it should be selected by default
    #$input_obj->options[] = ["value" => $k, "display" => $v];
  }
  $input_obj->class_label = "col-md-5";
  $input_obj->class_obj = "col-md-7";
  $rows[] = [$input_obj];


  /*
   * Individual Result Block
   */

  $input_obj = new stdClass();
  $input_obj->label = "Roll";
  $input_obj->name = "roll";
  $input_obj->type = "number";
  $input_obj->sdisplay = "none";
  $input_obj->class_label = "col-md-5";
  $input_obj->class_obj = "col-md-7";
  $rows[] = [$input_obj];

  $input_obj = new stdClass();
  $input_obj->label = "Registration (Optional)";
  $input_obj->name = "reg";
  #$input_obj->placeholder = "Registration for Marks Details";
  $input_obj->required = false;
  $input_obj->tooltip = true;
  $input_obj->tool_place = "top";
  $input_obj->tool_title = "Please give registartion number if you want to see detailed marks (if available). Without registration number you can only view Grades.";
  $input_obj->type = "number";
  $input_obj->sdisplay = "none";
  $input_obj->class_label = "col-md-5";
  $input_obj->class_obj = "col-md-7";
  $rows[] = [$input_obj];


  /*
   * Institute Result Block
   */

  $input_obj = new stdclass;
  $input_obj->label = "EIIN &nbsp;&nbsp;&nbsp;<button id=\"eiin_finder_tree\" type=\"button\" class=\"btn btn-primary\"> <span class=\"glyphicon glyphicon-search\"></span>Tree</button>&nbsp;<button id=\"eiin_finder_tbl\" type=\"button\" class=\"btn btn-primary\"> <span class=\"glyphicon glyphicon-search\"></span>List</button>";
  $input_obj->name = "eiin";
  $input_obj->type = "number";
  $input_obj->sdisplay = "none";
  $input_obj->class_label = "col-md-5";
  $input_obj->class_obj = "col-md-7";
  $rows[] = [$input_obj];


  /*
   * District Result Block
   */
  $input_obj = new stdclass;
  $input_obj->label = "District";
  $input_obj->name = "dcode";
  $input_obj->type = "select";
  $input_obj->options = [];
  $input_obj->sdisplay = "none";
  $input_obj->class_label = "col-md-5";
  $input_obj->class_obj = "col-md-7";
  $rows[] = [$input_obj];

  /*
   * Centre Result Block
   */
  $input_obj = new stdclass;
  $input_obj->label = "Centre";
  $input_obj->name = "ccode";
  $input_obj->type = "select";
  $input_obj->options = [];
  $input_obj->sdisplay = "none";
  $input_obj->class_label = "col-md-5";
  $input_obj->class_obj = "col-md-7";
  $rows[] = [$input_obj];




  /*
   * captcha
   */

  $input_obj = new stdClass();
  #$input_obj->label = "Security Key &nbsp; <img id=\"captcha_img\" src=\"\" height=40 width=80 alt=\"Refresh to get\">&nbsp;<button id=\"captcha_reload\" type=\"button\" class=\"btn btn-md btn-danger\"> <span class=\"glyphicon glyphicon-refresh\"></span>&nbsp;</button>";
  $input_obj->label = "Security Key (4 digits) &nbsp; <img id=\"captcha_img\" src=\"\" alt=\"Refresh to get\">&nbsp;<button id=\"captcha_reload\" type=\"button\" class=\"btn btn-md btn-danger\"> <span class=\"glyphicon glyphicon-refresh\"></span>&nbsp;Reload</button>";
  $input_obj->name = "captcha";
  $input_obj->placeholder = "Type the digits visible on the image";
  $input_obj->required = true;
  $input_obj->autocomplete = "off";
  $input_obj->sdisplay = "none";
  #$input_obj->tooltip = true;
  #$input_obj->tool_place = "top";
  #$input_obj->tool_title = "Reload if you don't see security key image.";
  #$input_obj->type = "text";
  $input_obj->type = "number";
  $input_obj->class_label = "col-md-7 vcenter";
  $input_obj->class_obj = "col-md-5 vcenter";
  $rows[] = [$input_obj];


  $input_obj = new stdClass();
  $input_obj->label = "";
  $input_obj->type = "submit";
  $input_obj->name = "submit";
  $input_obj->value = "Get Result";
  $input_obj->sdisplay = "none";
  $input_obj->class_label = "";
  $input_obj->class_obj = "";
  $input_obj->class = "btn btn-success center-block";
  $rows[] = [$input_obj];

  /*
  $input_obj = new stdClass();
  $input_obj->div_end = true;
  $rows[] = [$input_obj];
  */

  $formgen = gen_form($rows,$form_id,$form_action);
  $output .= <<<EOT
<div id="page-wrapper">
  <div class="row">
    <div class="$form_class">
      <div class="page-header text-center" id="page-header">$page_title</div>
    </div>
  </div>

  <div class="row">
    <div class="row buttons" id="buttons_up"></div>
    <br/>
    <div class="$form_class">
      <div id="result_display"></div>

      <div class="panel panel-default">
        <div class="panel-heading">$panel_heading</div>

        <div class="panel-body">
	  <div class="row">
	    <div class="$form_class">$formgen</div>
	  </div>
	</div>
      </div>
    </div>
    <div class="row buttons" id="buttons_down"><br/></div>
    <br/>
  </div>
</div>
EOT;

  #$footer_items = app_get_map_items($app, "footer_map_file");
  #print_r($footer_items);
  $app->footer_developed_by = "Nixtec Systems";
  $app->footer_owned_by = "Ministry of Education, Bangladesh";

  $output .= app_get_footer($app);

  return $output;
}

?>
