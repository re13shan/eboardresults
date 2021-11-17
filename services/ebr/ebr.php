<?php

// __xx are internal things
// any function that needs to work with sessions must have &$args


require_once(__DIR__ . '/ebr_funcs.php');

$fnlist['ebr'] = [

  '__cfg' => [
    'app' => 'ebr',
    'sidname' => 'EBRSESSID2', # suffix added to not to clash with other versions
    'timer_interval_ms' => 5000,
    'timer_callback' => 'ebr_cache_cleanup_cb',
    'sess_expire' => 86400,
    'sess_usage_threshold' => 0.80,
    'getres_expire' => 600, # 10 minutes to cache result data (so that new results are fetched upon updates
    'getres_usage_threshold' => 0.80,
    'sendfile_prefix_pdf' => '/result/inst/pdf',
    'data_root_pdf' => __DIR__ . '/data_root/pdf',
    'pdfviewer_file' => '/app/inst/pjs-latest/web/viewer.html',
    'justfunc' => [ 'stat', 'api' ], # Just call the function, without the __on_req_start/end
  ],
  '__ckey_config' => [
    'btree' => ['board', 'exam', 'year'],
    'btree_session' => ['__board', '__exam', '__year'], # __ is for taking them from session
    'btbl' => ['board', 'exam', 'year'],
    'app_home' => ['__perm', 'rtype', 'board', 'exam', 'year'],
    'getres_1' => ['board', 'exam', 'year', 'roll', 'reg'],
    'getres_2' => ['board', 'exam', 'year', 'eiin'],
    'getres_3' => ['board', 'exam', 'year', 'roll'],
    'getres_4' => ['board', 'exam', 'year', 'ccode', 'dcode'],
    'getres_5' => ['board', 'exam', 'year', 'dcode'],
    'getres_6' => ['board', 'exam', 'year', 'eiin'],
    'getres_7' => ['board', 'exam', 'year'],
    'dlist' => ['board', 'exam', 'year'],
    'clist' => ['board', 'exam', 'year', 'dcode'],
  ],

  'init' => function ($args=null) use (&$fnlist) {
    $ntestimonials = 1000;
    $nsessions = 1000000;
    $sesslen = 1024;
    $nboards = 50;
    //$nresults = 3000000;
    $nresults = 100000; // partially storing
    $ninsts = 20000;
    $ncenters = 2000;
    $ndists = 200;
    $ngsessions = $ninsts + $ncenters + $ndists + $nboards;
    $gsesslen = $sesslen;
    $nhome = 5000; // currently only few types: normal, combination of rtype=x, board=x, exam=x, year=x, normal, with testimonial

    if (isset($testing) && $testing == true) {
      // Testing Values
      $ntestimonials = 10;
      $nsessions = 10000;
      $nsessions = 5; // for extreme testing (for short time)
      $nboards = 50;
      //$nresults = 3000000;
      $nresults = 1000; // partially storing
      $nresults = 5; // for extreme testing (for short time)
      $ninsts = 200;
      $ncenters = 20;
      $ndists = 20;
      $ngsessions = $ninsts + $ncenters + $ndists + $nboards;
      $gsesslen = $sesslen;
      $nhome = 50; // currently only few types: normal, combination of rtype=x, board=x, exam=x, year=x, normal, with testimonial
    }




    $nresconfig = $args['nresconfig'] ?? 1024; // board-wise configuration and various other configurations, e.g., defs, notpublished, notice, etc.
    $resconfiglen = $args['resconfiglen'] ?? 64 * 1024; // subject code json files are some big
    $cache_config = [
      'resconfig' => ['title' => 'Various Configuration', 'nrows' => $nresconfig, 'ncols' => 1, 'cols' => [
      	['name' => 'conf', 'len' => $resconfiglen], // wish nothing will exceed this limit (even though mostly space will be wasted, but will save lots of file I/O
      ]],
      'counter' => ['title' => 'Result Counter', 'nrows' => $ngsessions, 'ncols' => 1,'cols' => [['name' => 'cnt', 'type' => 'num', 'len' => 8]]],
      'gsession' => ['title' => 'Result Session Restore', 'nrows' => $ngsessions, 'ncols' => 1,'cols' => [['name' => 'res', 'len' => $gsesslen]]],
      'btree' => ['title' => 'Board Tree', 'nrows' => $nboards, 'ncols' => 1, 'cols' => [['name' => 'res', 'len' => 2 * 1024 * 1024]]],
      'btree_session' => ['title' => 'Board Tree with Result Summary', 'nrows' => $nboards, 'ncols' => 1, 'cols' => [['name' => 'res', 'len' => 2 * 1024 * 1024]]],
      'btbl' => ['title' => 'Board Table', 'nrows' => $nboards, 'ncols' => 1, 'cols' => [['name' => 'res', 'len' => 1024 * 1024]]],
      'app_home' => ['title' => 'App Home', 'nrows' => $nhome, 'ncols' => 1, 'cols' => [['name' => 'res', 'len' => 16 * 1024]]],
      'getres_1' => ['title' => 'Detailed Result', 'nrows' => $nresults, 'ncols' => 1, 'cols' => [['name' => 'res', 'len' => 4 * 1024]]],
      'getres_2' => ['title' => 'Institution Result', 'nrows' => $ninsts, 'ncols' => 1, 'cols' => [['name' => 'res', 'len' => 2 * 1024]]],
      'getres_3' => ['title' => 'Testimonial Service', 'nrows' => $ntestimonials, 'ncols' => 1, 'cols' => [['name' => 'res', 'len' => 4 * 1024]]],
      'getres_4' => ['title' => 'Center Result', 'nrows' => $ncenters, 'ncols' => 1, 'cols' => [['name' => 'res', 'len' => 16 * 1024]]],
      'getres_5' => ['title' => 'District Result', 'nrows' => $ndists, 'ncols' => 1, 'cols' => [['name' => 'res', 'len' => 512 * 1024]]],
      'getres_6' => ['title' => 'Institution Analytics', 'nrows' => $ninsts, 'ncols' => 1, 'cols' => [['name' => 'res', 'len' => 4 * 1024]]],
      'getres_7' => ['title' => 'Board Analytics', 'nrows' => $nboards, 'ncols' => 1, 'cols' => [['name' => 'res', 'len' => 4 * 1024]]],
      'dlist' => ['title' => 'District Listing', 'nrows' => $nboards, 'ncols' => 1, 'cols' => [['name' => 'res', 'len' => 4 * 1024]]], // Upon selection of Board, list of districts appear
      'clist' => ['title' => 'Center Listing', 'nrows' => $ndists, 'ncols' => 1, 'cols' => [['name' => 'res', 'len' => 8 * 1024]]], // Upon selection of District, list of centers appear
    ];



    $fnlist['ebr']['__timer_config']  = [];
    foreach ($cache_config as $k => $config) {
      echo "Configuring Cache [{$config['title']}]\n";
      $fargs = array_merge(['app' => 'ebr', 'id' => $k], $config);
      $fnlist['cache']['addcfg']($fargs);
      $fnlist['ebr']['__timer_config'][$k] = [ 'app' => 'ebr', 'id' => $k, 'interval_ms' => $fnlist['ebr']['__cfg']['timer_interval_ms'], 'callback' => $fnlist['ebr']['__cfg']['timer_callback'], 'expire' => $fnlist['ebr']['__cfg']['getres_expire'], 'threshold' => $fnlist['ebr']['__cfg']['getres_usage_threshold'], 'locked' => new Swoole\Atomic(0) ];
    }
    $fnlist['session']['init']([ 'app' => 'ebr', 'id' => 'session', 'nsessions' => $nsessions, 'sesslen' => $sesslen ]);
    $fnlist['ebr']['__timer_config']['session'] = [ 'app' => 'ebr', 'id' => 'session', 'interval_ms' => $fnlist['ebr']['__cfg']['timer_interval_ms'], 'callback' => $fnlist['ebr']['__cfg']['timer_callback'], 'expire' => $fnlist['ebr']['__cfg']['sess_expire'], 'threshold' => $fnlist['ebr']['__cfg']['sess_usage_threshold'], 'locked' => new Swoole\Atomic(0) ];

    // now configure DB Pool (password should load from external location)
    $dbconfig = [ 'title' => 'MySQL Pool', 'host' => '127.0.0.1', 'port' => 3314, 'sock' => '/var/run/mysqld/mysqld-nixtec.sock', 'dbname' => 'ebarchive', 'charset' => 'utf8mb4', 'username' => 'resultuser', 'password' => 'resultuserpasswordhere' ];
    $fnlist['mysqlpool']['init'](['app' => 'ebr', 'config' => $dbconfig ]); // configure DB Pool

    // configure captcha
    $fnlist['captcha']['init']([ 'app' => 'ebr' ]);

    $fnlist['ebr']['configure']();

    return [ 200, 'ebr App initialized' ];
  }, 


  // no argument
  'configure' => function ($args=null) use (&$fnlist) { // or reconfigure
    echo "Loading service configuration to cache\n";

    $curtime = time();


    //$dprefix = "services/ebr";
    $dprefix = __DIR__;

    $fargs = ['app' => 'ebr', 'id' => 'resconfig' ];
    list ($code, $data) = $fnlist['cache']['reset']($fargs); // empty the cache, otherwise stale data may be left around
    //echo $data . "\n";

    # initialize counters
    list ($code, $data) = $fnlist['cache']['reset']([ 'app' => 'ebr', 'id' => 'counter' ]); // empty the cache, otherwise stale data may be left around
    $eybr = implode('/', [ '0', '0', '0', '0' ]);
    $fnlist['cache']['set']([ 'app' => 'ebr', 'id' => 'counter', 'key' => $eybr, 'val' => [ 'cnt' => $curtime, '__ts' => $curtime ] ]); # Increase by 1, or pass 'incrby' as argument


    $defs_file = "{$dprefix}/common_defs.php";
    require($defs_file); // sets $__defs
    $cur_year = date("Y");
    $min_year = 1996;
    $year_map = [];
    for ($i = $cur_year; $i >= $min_year; $i--) {
      $year_map[$i] = $i;
    }
    $__defs['year_map'] = $year_map;


    $fargs["key"] = basename($defs_file);
    $fargs["val"] = [ 'conf' => serialize($__defs) ];
    $fnlist['cache']['set']($fargs);

    $notpublished = [];
    $npubfiles = glob("{$dprefix}/" . "*.notpublished");
    if ($npubfiles !== false) { // not error occurred
      foreach ($npubfiles as $npubfile) {
        $notpublished[] = basename($npubfile);
      }
    }

    //print_r($npubfile);
    $fargs["key"] = 'notpublished';
    $fargs["val"] = [ 'conf' => serialize($notpublished) ];
    $fnlist['cache']['set']($fargs);

    $res_config_file = glob("{$dprefix}/" . "res_config/*_config.php");
    if ($res_config_file === false) {
      $res_config_file = [];
    }
    //print_r($res_config_file);
    foreach ($res_config_file as $res_cfg) {
      $res_config = [];
      include($res_cfg);
      $fargs["key"] = basename($res_cfg);
      $fargs["val"] = [ 'conf' => serialize($res_config) ]; // res_config is updated by the file
      $fnlist['cache']['set']($fargs);
    }

    $sub_cfg = "{$dprefix}/sub_config/sub_config.php";
    $sub_config = [];
    include($sub_cfg);
    $fargs["key"] = basename($sub_cfg);
    $fargs["val"] = [ 'conf' => serialize($sub_config) ]; // sub_config is updated by the file
    $fnlist['cache']['set']($fargs);

    $sub_config_json = glob("{$dprefix}/sub_config/*_sub_*.json");
    //print_r($sub_config_json);
    foreach ($sub_config_json as $sub_cfg) {
      $fargs["key"] = basename($sub_cfg);
      $sub_config_data = file_get_contents($sub_cfg);
      $fargs["val"] = [ 'conf' => $sub_config_data ];
      $fnlist['cache']['set']($fargs);
    }

    # api configuration
    $api_cfg = "{$dprefix}/api_config.php";
    $api_config = [];
    include($api_cfg); // api_config is updated by the file
    $fargs["key"] = basename($api_cfg);
    $fargs["val"] = [ 'conf' => serialize($api_config) ]; // sub_config is updated by the file
    $fnlist['cache']['set']($fargs);

    // now we have all configuration data in Cache Table

  },


  // return value of __on_req_start will be added to 'args['env']' for application
  '__on_req_start' => function ($args=null) use (&$fnlist) {
    list($code, $sdata) = $fnlist['session']['start']([ 'app' => 'ebr', 'request' => $args['request'], 'response' => $args['response'], 'sidname' => $fnlist['ebr']['__cfg']['sidname'], 'expire' => $fnlist['ebr']['__cfg']['sess_expire'] ]);
    return [ '__sid' => $sdata[0], '__session' => $sdata[1] ];
  },

  // return value of __on_req_end will be discarded by caller main request handler
  '__on_req_end' => function ($args=null) use (&$fnlist) {
    return $fnlist['session']['save']([ 'app' => 'ebr', 'request' => $args['request'], 'response' => $args['response'], 'sid' => $args['env']['__sid'], 'sdata' => $args['env']['__session'] ]);
  },

  'dot' => function ($args=null) use (&$fnlist) {
    return $fnlist['core']['dot']($args); // calling another service, so not sending code,data array
  },

  'about' => function ($args=null) use ($fnlist) {
    return [ 200, 'Developed and Maintained by Nixtec Systems. Developer and Maintainer: Md. Ayub Ali (ayub@nixtecsys.com, mrayub@gmail.com, +8801911192000).' ];
  },

  '__serve_cached' => function (&$args=null) use (&$fnlist) {

    $env = &$args['env'];
    $_SESSION = &$env['__session'];
    $_REQUEST = $args['reqargs'];

    $id = $args['id'];
    $expire = $args['expire'];
    $curtime = $env['server']['TIME'] ?? time();


    //$result_type = $_REQUEST['result_type'] ?? '1';
    //$id = 'getres_' . $result_type;
    $serve_cached = false;
    $serve_code = 410;
    $serve_data = null;
    $serve_key = '';

    if (isset($fnlist['ebr']['__ckey_config'][$id])) {
      $ckey_array = [];
      foreach ($fnlist['ebr']['__ckey_config'][$id] as $k) {
        $v = '';
        if (strncmp($k, '__', 2) == 0) {
	  $k = substr($k, 2);
	  $v = $_SESSION[$k] ?? '';
	} else {
	  $v = $_REQUEST[$k] ?? '';
	}
        $ckey_array[] = $v;
      }
      $serve_key = implode("/", $ckey_array);
      list ($code, $serve_data) = $fnlist['cache']['get']([ 'app' => 'ebr', 'id' => $id, 'key' => $serve_key, 'col' => 'res' ]);
      if ($code == 200) {
        $serve_cached = true;
	list ($code1, $data1) = $fnlist['cache']['get']([ 'app' => 'ebr', 'id' => $id, 'key' => $serve_key, 'col' => '__ts' ]);
	if (($curtime - $data1) > $expire) {
	  //echo "*** Cache Expired\n";
	  $serve_cached = false;
	}
      }
    }
    $serve_code = $serve_cached == true ? 200 : 410;
    return [ $serve_code, [ $serve_key, $serve_data ] ];
  },

  'home' => function (&$args=null) use (&$fnlist) {

    $env = &$args['env'];
    $__sid = &$env['__sid'];
    $_SESSION = &$env['__session'];
    $_REQUEST = $args['reqargs'];
    $curtime = $env['server']['TIME'] ?? time();

    $id = 'app_home';

    $expire = $fnlist['ebr']['__cfg']['getres_expire'];
    $args['id'] = $id;
    $args['expire'] = $expire;
    list ($code, $sdata) = $fnlist['ebr']['__serve_cached']($args);
    list ($ckey, $data) = $sdata;

    if ($code != 200) { // don't use cached data

      $fargs = ['app' => 'ebr', 'id' => 'resconfig' ];
      $fargs['key'] = 'common_defs.php';
      $fargs['col'] = 'conf';
      list ($code, $data) = $fnlist['cache']['get']($fargs);
      $defs = unserialize($data);

      $env['request'] = &$_REQUEST;
      $env['defs'] = $defs;

      $data = ebr_render_app($env);

      // save to cache
      if ($code == 200 && $ckey != '') {
        #echo "*** Saving to Cache [id=$id, key=$ckey]\n";
	$fnlist['cache']['set']([ 'app' => 'ebr', 'id' => $id, 'key' => $ckey, 'val' => [ 'res' => $data, '__ts' => $curtime ] ]);
      }
    } else {
      #echo "*** Serving Cached Data [id=$id, key=$ckey]\n";
    }

    return [ $code, $data ];
    
  },

  'captcha' => function (&$args=null) use (&$fnlist) {

    $env = &$args['env'];
    $__sid = &$env['__sid'];
    $_SESSION = &$env['__session'];

    // get a random captcha
    list ($code, $cinfo) = $fnlist['captcha']['get']([ 'app' => 'ebr' ]);
    //print_r($cinfo);

    $_SESSION['captcha'] = $cinfo['word'];
    $response = &$args['response'];
    $response->header("Content-Type", $cinfo['file_mime']);
    $env['x-accel-redirect'] = $cinfo['sendfile_prefix'] . '/' . $cinfo['index'] . '.' . $cinfo['file_ext'];

    return [ $code, "ok" ];
  },

  'pdl' => function (&$args=null) use (&$fnlist) {

    $env = &$args['env'];
    $_SESSION = &$env['__session'];
    #print_r($_SESSION);
    $response = &$args['response'];

    $code = 403;
    $data = "Forbidden";
    if (($pdf_path = $_SESSION['pdf_path'] ?? null) != null) {
      $response->header("Content-Type", "application/pdf");
      $response->header("Content-Transfer-Encoding", "binary");
      $response->header("Content-Disposition", "attachment; filename=\"". basename($pdf_path) ."\"");
      $env['x-accel-redirect'] = $pdf_path;
      $code = 200;
      $data = "OK";
    }

    return [ $code, $data ];
  },

  'list' => function (&$args=null) use (&$fnlist) {

    $env = &$args['env'];
    $__sid = &$env['__sid'];
    $_SESSION = &$env['__session'];
    $_REQUEST = $args['reqargs'];
    $response = &$args['response'];
    $curtime = $env['server']['TIME'] ?? time();

    $response->header("Content-Type", "application/json");

    $ids = array('btree', 'dlist', 'clist');
    $id = $_REQUEST['id'];
    if (!in_array($id, $ids)) {
      return [ 403, 'Forbidden' ];
    }

    $cid = $id;

    // now check if result is found in cache
    //$id = 'btree';
    if ($id == 'btree') {
      if (!isset($_REQUEST['board']) && isset($_SESSION['btree']) && isset($_SESSION['board']) && isset($_SESSION['exam']) && isset($_SESSION['year'])) {
	$_REQUEST['board'] = $_SESSION['board'];
	$_REQUEST['exam'] = $_SESSION['exam'];
	$_REQUEST['year'] = $_SESSION['year'];
	$cid = 'btree_session';
      } else if (isset($_REQUEST['type']) && $_REQUEST['type'] == 'tbl') { # handle btree vs btbl in cache
	$cid = 'btbl';
      }
    }

    $expire = $fnlist['ebr']['__cfg']['getres_expire'];
    $args['id'] = $cid;
    $args['expire'] = $expire;
    list ($code, $sdata) = $fnlist['ebr']['__serve_cached']($args);
    list ($ckey, $data) = $sdata;

    $args['id'] = $id;
    if ($code != 200) { // don't use cached data
      list ($code, $data) = $fnlist['ebr']["__{$id}_impl"]($args);
      // save to cache
      if ($code == 200 && $ckey != '') {
        #echo "*** Saving to Cache [id=$cid, key=$ckey]\n";
	$fnlist['cache']['set']([ 'app' => 'ebr', 'id' => $cid, 'key' => $ckey, 'val' => [ 'res' => $data, '__ts' => $curtime ] ]);
      }
    } else {
      #echo "*** Serving Cached Data [id=$cid, key=$ckey]\n";
    }

    return [ $code, $data ];
  },

  '__btree_impl' => function (&$args=null) use (&$fnlist) {

    $env = &$args['env'];
    $_SESSION = &$env['__session'];
    $_REQUEST = $args['reqargs'];

    $code = 403;

    $fs = "|";
    $rs = "\n";

    $bobj = new stdClass();

    $result = false;
    $success = false;

    if (isset($_REQUEST['board']) && isset($_REQUEST['exam']) && isset($_REQUEST['year'])) {
      $board = preg_replace("/[^a-z]+/", "", $_REQUEST['board']);
      $exam = preg_replace("/[^a-z]+/", "", $_REQUEST['exam']);
      $year = preg_replace("/[^0-9]+/", "", $_REQUEST['year']);

      if ($exam == "dibs") {
	$board = "dibs";
	$exam = "hsc";
      }
    } else {
      if (!isset($_SESSION['btree']) || $_SESSION['btree'] == false) {
	$bobj = new stdClass();
	goto out;
      }

      $result = true;

      $board = $_SESSION['board'];
      $exam = $_SESSION['exam'];
      $year = $_SESSION['year'];
    }

    if ($board == "" || $exam == "" || $year == "") {
      goto out;
    }

    $file = $fnlist['ebr']['__cfg']['data_root_pdf'] . "/${exam}/${year}/${board}/${exam}_${board}_${year}_inst.csv";
    if (!file_exists($file)) {
      $bobj = new stdClass();
      goto out;
    }
    $contents = file_get_contents($file);
    $lines = explode($rs, $contents);
    unset($contents); // free resources

    $istbl = false;
    $tbl_cols = false;

    if (isset($_REQUEST['type']) && $_REQUEST['type'] == 'tbl') {
      $istbl = true;
      if (isset($_REQUEST['cols']) && $_REQUEST['cols'] != '') {
	$tbl_cols = explode(",", $_REQUEST['cols']);
      }
    }

    if (!$istbl) {
      $board_name = strtoupper($board);
      $bobj = new stdClass();
      $bobj->name = $board_name . ($board_name == "DIBS"? "" : " BOARD");
      $title = strtoupper($exam) . "/Equivalent Exam-" . $year;
      $bobj->title = $board_name . ", " . $title;

      $zilla_list = array();
      $thana_list = array();
      $eiin_t_list = array();

      $z_map = array();
      $t_map = array();
      $e_map = array();
      $eiin_t_list = array();


    }

    $cnt = 0;
    $tbl_rows = array();
    foreach ($lines as $line) {
      $f = explode($fs, $line);
      if ($istbl) {
	if (!$tbl_cols) $tbl_cols = $f;
      }
      $nf = count($f);

      if ($nf < 5) continue;

      if ($cnt++ == 0) {
	// parse header
	for ($i = 0; $i < $nf; $i++) {
	  $k = $f[$i];
	  $kmap[$k] = $i;
	}
	continue; // no need to go down
      }


      if ($istbl) {
	$cols = array();
	foreach ($tbl_cols as $k) {
	  $cols[] = $f[$kmap[$k]];
	}
	$tbl_rows[] = $cols;
	continue; // no need to go down, csv is done
      }


      $eiin = $f[$kmap["eiin"]];
      $icode = $f[$kmap["i_code"]];
      $zcode = $f[$kmap["z_code"]];
      $tcode = $f[$kmap["t_code"]];
      $iname = $f[$kmap["i_name"]];
      $zname = $f[$kmap["zilla"]];
      $tname = $f[$kmap["thana"]];
      $bname = $f[$kmap["board_name"]];
      if ($result) {
	$app = $f[$kmap["tot_app"]];
	$pass = $f[$kmap["tot_pass"]];
	$percent = $f[$kmap["percent"]];
	$gpa5 = $f[$kmap["gpa5"]];
      }

      if ($eiin == "" && $icode != "") $eiin = $icode;

      if ($zname == "" && $zcode != "") $zname = "Zilla ($zcode)";
      if ($tname == "" && $tcode != "") $tname = "Thana ($tcode)";

      // technical board didn't provide zcode and tcode, handle it
      if ($zcode == "" && $zname != "") $zcode = $zname;
      if ($tcode == "" && $tname != "") $tcode = $tname;

      if ($zcode == "" && $zname == "") {
	$zcode = $zname = "Zilla (N/A)";
      }
      if ($tcode == "" && $tname == "") {
	$tcode = $tname = "Thana (N/A)";
      }

      $z_map[$zcode] = $zname;
      $t_map[$zcode][$tcode] = $tname;
      $e_map[$eiin] = $iname;
      if ($result) {
	$info = new stdClass();
	$info->app = $app;
	$info->pass = $pass;
	$info->percent = $percent;
	$info->gpa5 = $gpa5;
	//$e_title[$eiin] = "Total Appeared: $app, Total Passed: $pass, Percentage of Pass: $percent, GPA 5: $gpa5";
	$e_info[$eiin] = $info;
      }
      $eiin_t_list[$zcode][$tcode][] = $eiin;
    }
    $code = 200;

    if ($istbl) {
      $bobj = new stdClass();
      $bobj->data = $tbl_rows;

      $success = true;
      goto out;
    }

    foreach ($eiin_t_list as $zcode => $x) {
      $zobj = new stdClass();
      $zobj->name = $z_map[$zcode];

      // Dhaka is divided into two regions, but in the result Database both are 'DHAKA MAHANAGARI'. So we're going to add suffix to the second one
      if (@$seen[$zobj->name]++ > 0) {
	$zobj->name .= "-" . $seen[$zobj->name];
      }

      foreach ($x as $tcode => $eiins) {
	$tobj = new stdClass();
	$tobj->name = $t_map[$zcode][$tcode];
	foreach ($eiins as $eiin) {
	  $eobj = new stdClass();
	  $eobj->name = $e_map[$eiin] . " (EIIN: $eiin)";
	  if ($result) {
	    $eobj->info = $e_info[$eiin];
	    //$eobj->title = $e_title[$eiin];
	  }
	  $tobj->children[] = $eobj;
	}
	sort($tobj->children);
	$zobj->children[] = $tobj;
      }
      sort($zobj->children);
      $bobj->children[] = $zobj;
    }

    if (isset($bobj->children)) {
      sort($bobj->children);
    }

    out:

    return [ $code, json_encode($bobj) ];
  },

  '__dlist_impl' => function (&$args=null) use (&$fnlist) {

    $env = &$args['env'];
    $_SESSION = &$env['__session'];
    $_REQUEST = $args['reqargs'];

    $code = 403;


    $dists = [];
    if (isset($_REQUEST['board']) && isset($_REQUEST['exam']) && isset($_REQUEST['year'])) {
      $board = preg_replace("/[^a-z]+/", "", $_REQUEST['board']);
      $exam = strtolower(preg_replace("/[^a-zA-Z]+/", "", $_REQUEST['exam']));
      $year = preg_replace("/[^0-9]+/", "", $_REQUEST['year']);

      if ($exam == "dibs") {
	$exam = "hsc";
	$board = "dibs";
      }

      $nr = 0;
      $dist_file = $fnlist['ebr']['__cfg']['data_root_pdf'] . "/${exam}/${year}/${board}/dist.csv";
      if (file_exists($dist_file)) {
	$lines = explode("\n", trim(file_get_contents($dist_file)));
	$sorted = [];
	foreach ($lines as $line) {
	  $nr++;
	  if ($nr == 1) continue;
	  $parts = explode("|", $line);
	  $code = $parts[0];
	  $name = $parts[1];
	  $sorted[$code] = $name;
	}
	asort($sorted);
	foreach ($sorted as $code => $name) {
	  $dobj = new stdClass();
	  $dobj->code = $code;
	  $dobj->name = $name;
	  $dists[] = $dobj;
	}

        $code = 200;
	goto out;
      }
    }

    out:
    return [ $code, json_encode($dists) ];
  },

  '__clist_impl' => function (&$args=null) use (&$fnlist) {
    $env = &$args['env'];
    $_SESSION = &$env['__session'];
    $_REQUEST = $args['reqargs'];

    $code = 403;

    $cents = [];
    if (isset($_REQUEST['board']) && isset($_REQUEST['exam']) && isset($_REQUEST['year']) && isset($_REQUEST['dcode'])) {
      $board = preg_replace("/[^a-z]+/", "", $_REQUEST['board']);
      $exam = strtolower(preg_replace("/[^a-zA-Z]+/", "", $_REQUEST['exam']));
      $year = preg_replace("/[^0-9]+/", "", $_REQUEST['year']);
      $dcode = preg_replace("/[^0-9]+/", "", $_REQUEST['dcode']);

      if ($exam == "dibs") {
	$exam = "hsc";
	$board = "dibs";
      }

      $nr = 0;
      $cent_file = $fnlist['ebr']['__cfg']['data_root_pdf'] . "/${exam}/${year}/${board}/dist_cent_map.csv";
      if (file_exists($cent_file)) {
	$lines = explode("\n", trim(file_get_contents($cent_file)));
	$sorted = [];
	foreach ($lines as $line) {
	  $nr++;
	  if ($nr == 1) continue;
	  $parts = explode("|", $line);
	  $tmp_dcode = $parts[0];
	  if ($dcode != $tmp_dcode) continue;

	  $code = $parts[1];
	  $name = $parts[2];
	  $sorted[$code] = $name;
	}

	asort($sorted);

	foreach ($sorted as $code => $name) {
	  $cobj = new stdClass();
	  $cobj->code = $code;
	  $cobj->name = $name;
	  $cents[] = $cobj;
	}
	$code = 200;
	goto out;
      }
    }

    out:

    return [ $code, json_encode($cents) ];
  },

  'api' => function (&$args=null) use (&$fnlist) {

    $robj = new stdClass();
    $robj->status = 1;
    $robj->msg = "Please contact your service provider.";
    $robj->res = "";
    $rcode = 403;

    $fargs = ['app' => 'ebr', 'id' => 'resconfig' ];
    $fargs['key'] = "api_config.php";
    $fargs['col'] = 'conf';
    $api_config = [];
    list ($code, $data) = $fnlist['cache']['get']($fargs);
    if ($code == 200) {
      $api_config = unserialize($data);
    }

    $env = &$args['env'];
    #$__sid = &$env['__sid'];
    #$_SESSION = &$env['__session'];
    $_REQUEST = &$args['reqargs']; # for API we are injecting 'result_type'=1 in argument, so using reference here
    #$curtime = $env['server']['TIME'] ?? time();



    $user = $_REQUEST['user'];
    $token = $_REQUEST['token'];
    $roll = $_REQUEST['roll'] ?? '';

    $uinfo = $api_config['api_users'][$user] ?? [];
    $ip = $env['server']['REMOTE_ADDR'];

    if (empty($uinfo)
      || ((is_scalar($uinfo['ipallow']) && $uinfo['ipallow'] != '*' && $uinfo['ipallow'] != $ip) || (is_array($uinfo['ipallow']) && !in_array($ip, $uinfo['ipallow'])))
      || ($token != $uinfo['token'])
      || (isset($uinfo['rollpattern']) && $uinfo['rollpattern'] != '' && preg_match($uinfo['rollpattern'], $roll) !== 1)
    ) {
      return [ $rcode, json_encode($robj) ];
    }

    if (isset($_REQUEST['type'])) { # special type of API requested (not general result data)
      return $fnlist['ebr']['__api_impl']($args);
    }

    //list ($code, $data) = $fnlist['session']['expire']([ 'app' => 'ebr', 'expire' => $fnlist['ebr']['__cfg']['sess_expire'] ]);
    //echo $data . "\n";

    # Hit Counter
    #$eybr = implode('/', [ $_REQUEST['exam'] ?? '', $_REQUEST['year'] ?? '', $_REQUEST['board'] ?? '', $_REQUEST['result_type'] ?? '1' ]);
    #$fnlist['cache']['incr']([ 'app' => 'ebr', 'id' => 'counter', 'key' => $eybr, 'col' => 'cnt' ]); # Increase by 1, or pass 'incrby' as argument
    #print_r($fnlist['cache']['dump']([ 'app' => 'ebr', 'id' => 'counter' ]));

    if (!isset($_REQUEST['result_type'])) {
      $_REQUEST['result_type'] = '1';
    }
    $env['RESULT_API'] = true;
    $env['RESOLVE_SUBJECT'] = true;
    $env['RESOLVE_EIIN'] = true;
    $env['DISPLAY_REGNO'] = true;
    $env['DISPLAY_DOB'] = true;
    $env['DISPLAY_SESSION'] = true;



    // now check if result is found in cache
    #$result_type = $_REQUEST['result_type'] ?? '1';
    $result_type = '1';
    $id = 'getres_' . $result_type;
    #$expire = $fnlist['ebr']['__cfg']['getres_expire'];

    $args['id'] = $id;
    #$args['expire'] = $expire;

    # for API don't use Cache, otherwise cache may have privileged data
    # as not using cache, we should add rate-limiting mechanism, later.
    # if we find abuse
    list ($code, $data) = $fnlist['ebr']['__getres_impl']($args);

    return [ $code, $data ];

  },

  'getres' => function (&$args=null) use (&$fnlist) {
    $robj = new stdClass();
    $robj->status = 1;
    $robj->msg = "You need to provide a valid Security Key / CAPTCHA. Please check and try again.";
    $robj->res = "";
    $rcode = 403;

    //list ($code, $data) = $fnlist['session']['expire']([ 'app' => 'ebr', 'expire' => $fnlist['ebr']['__cfg']['sess_expire'] ]);
    //echo $data . "\n";

    $env = &$args['env'];
    $__sid = &$env['__sid'];
    $_SESSION = &$env['__session'];
    $_REQUEST = $args['reqargs'];
    $curtime = $env['server']['TIME'] ?? time();

    # Hit Counter
    $eybr = implode('/', [ $_REQUEST['exam'] ?? '', $_REQUEST['year'] ?? '', $_REQUEST['board'] ?? '', $_REQUEST['result_type'] ?? '1' ]);
    $fnlist['cache']['incr']([ 'app' => 'ebr', 'id' => 'counter', 'key' => $eybr, 'col' => 'cnt' ]); # Increase by 1, or pass 'incrby' as argument
    #print_r($fnlist['cache']['dump']([ 'app' => 'ebr', 'id' => 'counter' ]));


    $captcha = strtolower(trim($_REQUEST['captcha'] ?? 'none'));
    if (!isset($_SESSION['captcha']) || $captcha == '' || $captcha != $_SESSION['captcha']) {
      unset($_SESSION['captcha']);
      return [ $rcode, json_encode($robj) ];
    }
    unset($_SESSION['captcha']);

    $env['HUMAN_DETECTED'] = true;

    # Now set proper environment variables
    if (isset($_SESSION['nixtec_verified'])) {
      $env['NIXTEC_VERIFIED'] = true;
    }

    if (isset($_SESSION['marks_board'])) {
      $env['MARKS_BOARD'] = $_SESSION['marks_board'];
    }

    # for Testimonial Service
    if (isset($_SESSION['login_eiin'])) {
      $env['LOGIN_EIIN'] = $_SESSION['login_eiin'];
    }

    if (isset($_SESSION['perm'])) {
      $env['PERM'] = $_SESSION['perm'];
    }

    $env['RESOLVE_SUBJECT'] = true;
    $env['RESOLVE_EIIN'] = true;




    // now check if result is found in cache
    $result_type = $_REQUEST['result_type'] ?? '1';
    $id = 'getres_' . $result_type;
    $expire = $fnlist['ebr']['__cfg']['getres_expire'];

    $args['id'] = $id;
    $args['expire'] = $expire;
    list ($code, $sdata) = $fnlist['ebr']['__serve_cached']($args);
    list ($ckey, $data) = $sdata;


    if ($code != 200) { // don't use cached data
      list ($code, $data) = $fnlist['ebr']['__getres_impl']($args);
      // save to cache
      if ($code == 200 && $ckey != '') {
        #echo "*** Saving to Cache [id=$id, key=$ckey]\n";
	$fnlist['cache']['set']([ 'app' => 'ebr', 'id' => $id, 'key' => $ckey, 'val' => [ 'res' => $data, '__ts' => $curtime ] ]);
	if (in_array($result_type, array('2','4','5','7'))) { // this is to ensure that upon cleanup we can still serve PDF and SVG data from session
	  $fnlist['cache']['set']([ 'app' => 'ebr', 'id' => 'gsession', 'key' => $ckey, 'val' => [ 'res' => serialize($_SESSION), '__ts' => $curtime ] ]);
	}
      }
    } else {
      #echo "*** Serving Cached Data [id=$id, key=$ckey]\n";
      if (in_array($result_type, array('2','4','5','7'))) { // this is to ensure that upon cleanup we can still serve PDF and SVG data from session
	list ($code2, $data2) = $fnlist['cache']['get']([ 'app' => 'ebr', 'id' => 'gsession', 'key' => $ckey, 'col' => 'res' ]);
	if ($code2 == 200) {
	  $_SESSION = unserialize($data2);
	}
      }
    }

    return [ $code, $data ];

  },

  '__api_impl' => function (&$args=null) use (&$fnlist) {

    # this method should not access $_SESSION data, rather, caller should set environment variable properly in 'env'

    $robj = new stdClass();
    $robj->status = 1;
    $robj->msg = "Please check your input and try again.";
    $robj->res = "";
    $rcode = 403;

    $_REQUEST = $args['reqargs'];

    $board = "";
    $qtype = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $_REQUEST['type']));
    switch ($qtype) {
      case 'inst':
      if (isset($_REQUEST['eiin']) && $_REQUEST['eiin'] != '') {
        $ikey = "eiin"; # should be same as the column name in database table
      } else if (isset($_REQUEST['i_code']) && $_REQUEST['i_code'] != '') {
        $ikey = "i_code"; # should be same as the column name in database table
	$board = preg_replace("/[^a-zA-Z]+/", "", $_REQUEST['board'] ?? "");
	if ($board == "") goto noaccess;
      } else {
        goto noaccess;
      }
      $ival = preg_replace("/[^0-9]+/", "", $_REQUEST[$ikey]);
      if ($ival == "") goto noaccess;

      $exam = preg_replace("/[^a-zA-Z]+/", "", $_REQUEST['exam'] ?? '');
      if ($exam == "") goto noaccess;

      $sql = "SELECT * FROM {$exam}institute WHERE $ikey='$ival'";
      if ($board != "") {
	$sql .= " AND board_name='" . strtoupper($board) . "'";
      }

      $year = "";
      if (isset($_REQUEST['year']) && $_REQUEST['year'] != '') {
	$year = preg_replace("/[^0-9]+/", "", $_REQUEST['year']);
      }
      if ($year != "") {
	$sql .= " AND year='" . $year . "'";
      } else {
	$sql .= " ORDER BY year DESC";
      }
      $sql .= " LIMIT 1";

      $db = false;
      list ($code, $db) = $fnlist['pool']['get']([ 'app' => 'ebr', 'id' => 'mysqlpool' ]);

      $result = false;
      #echo $sql . "\n";
      try {
	$result = $db->query($sql);
      } catch (Exception $e) {
      }

      if ($result && $result->num_rows > 0) {
        $rcode = 200;
	$robj->status = 0;
	$robj->msg = 'Success';
        $robj->res = $result->fetch_object();
      }

      $fnlist['pool']['put']([ 'app' => 'ebr', 'id' => 'mysqlpool', 'handle' => $db ]);
      $db = false;

      break;
      default:
      break;
    }

noaccess:
    return [ $rcode, json_encode($robj) ];
  },

  '__getres_impl' => function (&$args=null) use (&$fnlist) {

    # this method should not access $_SESSION data, rather, caller should set environment variable properly in 'env'

    $robj = new stdClass();
    $robj->status = 1;
    $robj->msg = "Something is not right. Please check and try again.";
    $robj->res = "";
    $rcode = 403;



    $fargs = ['app' => 'ebr', 'id' => 'resconfig' ];

    $env = &$args['env'];
    $__sid = &$env['__sid'];
    $_SESSION = &$env['__session']; # group result still needs session, specially when btree is accessed
    $_REQUEST = $args['reqargs'];

    $fargs['key'] = 'common_defs.php';
    $fargs['col'] = 'conf';
    list ($code, $data) = $fnlist['cache']['get']($fargs);
    $defs = unserialize($data);
    //print_r($defs);

    $exams = array_keys($defs['exam_map']);
    $boards = array_keys($defs['board_map']);
    $years = array_keys($defs['year_map']);
    $result_types = array_keys($defs['result_type']);

    $requested_exam = strtolower($_REQUEST['exam'] ?? "");
    if (isset($defs['exam_translate'][$requested_exam])) {
      $requested_exam = $defs['exam_translate'][$requested_exam];
    }
    $requested_board = strtolower($_REQUEST['board'] ?? "");
    $requested_year = strtolower($_REQUEST['year'] ?? "");
    $requested_result_type = strtolower($_REQUEST['result_type'] ?? "");


    // Handling DIBS exam/board accordingly
    if ($requested_exam == "dibs") {
      $requested_board = "dibs";
      $requested_exam = "hsc";
    }


    if (!in_array($requested_exam, $exams) ||
	!in_array($requested_board, $boards) ||
	!in_array($requested_year, $years) ||
	!in_array($requested_result_type, $result_types) ||
	!in_array($requested_result_type, $defs['allowed_result_type'])) {

      $rcode = 403;
      $robj->status = 1;
      $robj->msg = "Please check exam/board/year/result_type and try again.";
      goto out;
    }

    $proceed = true;
    switch ($requested_result_type) {
      case '1':
      case '3':
	$requested_roll = preg_replace("/[^0-9]+/", "", $_REQUEST['roll'] ?? '');
	$requested_reg = preg_replace("/[^0-9]+/", "", $_REQUEST['reg'] ?? '');
	if (strlen($requested_roll) < 6) {
	  $proceed = false;
	  $robj->msg = "Invalid Roll number provided. Please check and try again.";
	}
	break;
      case '2':
      case '6':
	$requested_eiin = preg_replace("/[^0-9]+/", "", $_REQUEST['eiin'] ?? '');
	if (strlen($requested_eiin) < 4) { // we also allow institute code in case eiin is not available
	  $proceed = false;
	  $robj->msg = "Invalid EIIN or Institution Code provided. Please check and try again.";
	}
	break;
      case '4':
	$requested_ccode = preg_replace("/[^0-9]+/", "", $_REQUEST['ccode'] ?? '');
	if ($requested_ccode == "") {
	  $proceed = false;
	  $robj->msg = "Invalid Center Code provided. Please check and try again.";
	}
	break;
      case '5':
	$requested_dcode = preg_replace("/[^0-9]+/", "", $_REQUEST['dcode'] ?? '');
	if ($requested_dcode == "") {
	  $proceed = false;
	  $robj->msg = "Invalid District Code provided. Please check and try again.";
	}
	break;
      default:
        break;
    }

    if (!$proceed) {
      goto out;
    }

    $requested_board_upper = strtoupper($requested_board);

    $fargs['key'] = 'notpublished';
    $fargs['col'] = 'conf';
    list ($code, $data) = $fnlist['cache']['get']($fargs);
    $notpublished = unserialize($data);
    if (in_array("{$requested_exam}.{$requested_year}.notpublished", $notpublished)) {
      $robj->msg = "Result is not yet published for {$requested_exam}/equivalent-{$requested_year} exam. Please try later.";
      goto out;
    }
    //print_r($defs);

    // Group Result (Institution/Center/District)
    if (in_array($requested_result_type, $defs['allowed_result_type_group'])) {
      goto group_result;
    } else {
      goto individual_result;
    }

individual_result:

    $res_config_file = [
      "${requested_exam}_config.php",
      "${requested_exam}_${requested_year}_config.php",
      "${requested_board}_config.php",
      "${requested_board}_${requested_exam}_config.php",
      "${requested_exam}_${requested_board}_${requested_year}_config.php",
    ];

    $res_config = [];
    foreach ($res_config_file as $res_cfg) {
      $fargs["key"] = $res_cfg;
      $fargs["col"] = 'conf';
      list ($code, $data) = $fnlist['cache']['get']($fargs);
      if ($code == 200) {
	$res_config = array_merge($res_config, unserialize($data));
      }
    }

    if (isset($env['HUMAN_DETECTED']) && isset($env['CHECK_ENLISTED']) && in_array($requested_board, $defs['board_not_enlisted'])) {
      $robj->msg = strtoupper($defs['board_map'][$requested_board]) . " BOARD IS NOT ENLISTED. PLEASE VISIT WEBSITE FOR DETAILS: " . $defs['board_website_map'][$requested_board];
      goto out;
    }

    $suffix = "{$requested_board}_{$requested_year}";
    $result_table_name = "{$requested_exam}_{$suffix}";
    $institutes_table_name = "{$requested_exam}institute";

    $enable_details = true;
    $enable_showmarks = true;
    $enable_showtotal = true;

    $db = false;

    list ($code, $db) = $fnlist['pool']['get']([ 'app' => 'ebr', 'id' => 'mysqlpool' ]);

    $result = false;
    $nrows = 0;
    $rows = [];
    $sql = "SELECT * FROM $result_table_name WHERE roll_no='{$requested_roll}'";
    try {
      $result = $db->query($sql);
    } catch (Exception $e) {
      # happens usually if table doesn't exist
      $robj->msg = "Result with your requested criteria is not found. Please check and try again.";
      if (isset($env['RESULT_API'])) {
        if ($db->errno == 1146) { // 1146 = Table or View doesn't exist
	  $robj->status = 2;
	  $robj->msg = $db->error;
	}
      }
      goto out;
    }

    if ($result) {
      while ($row = $result->fetch_object()) {
	$rows[] = $row;
      }
    }
    $nrows = count($rows);

    if ($nrows > 0) {
      if ($nrows > 1 && $requested_reg == "") {
	$robj->msg = "Registration Number is Needed due to having multiple records with same roll number.";
	goto out;
      }
    } else {
      $robj->msg = "Result with your requested criteria is not found. Please check and try again.";
      if (isset($env['RESULT_API'])) {
        if ($db->errno == 1146) { // 1146 = Table or View doesn't exist
	  $robj->status = 2;
	  $robj->msg = $db->error;
	}
      }
      goto out;
    }

    $oldrow = $row = false;
    foreach ($rows as $row) {
      $oldrow = $row;
      if ($requested_reg != "" && $row->regno == $requested_reg) break;
    }

    $row = $oldrow;
    if ($result) {
      $result->free();
      $result = false;
    }

    if ($requested_result_type == "3") {
      if (isset($env['LOGIN_EIIN']) && $env['LOGIN_EIIN'] != $row->eiin) {
	$robj->msg = "You are not allowed to access this data.";
	goto out;
      }
    }

    $reg_required = false;
    if ($requested_reg != "") {
      $reg_required = true;
    }

    $reg_ok = false;
    if ($reg_required == true
	&& ($requested_reg == $row->regno || (isset($res_config['regno_match_partial']) && $res_config['regno_match_partial'] == true && substr_compare($row->regno, $requested_reg, 0, strlen($row->regno)) == 0))) { # handle partial match when full regno is not available in result database
      $REG_VERIFIED = true;
      $reg_ok = true;
    }

    if ($reg_required == true && $reg_ok == false) {
      $robj->msg = "Incorrect registration number. Please check and try again.";
      goto out;
    }


    if (isset($row->dob) && $row->dob != "") $row->dob = _get_dob($row->dob);
    else $row->dob = "";

    if (isset($row->stud_sex)) {
      switch ($row->stud_sex) {
	case 'M':
	case 'MALE':
	  $row->sex = 0;
	  break;
	case 'F':
	case 'FEMALE':
	  $row->sex = 1;
	  break;
	default:
	  $row->sex = $row->stud_sex;
	  break;
      }
      unset($row->stud_sex);
    }


    if (isset($env['RESOLVE_EIIN'])) {
      # Try to resolve institution name in following order:
      # Trial 1 [retry==2]: Find exact match with 'eiin', 'board', 'year' - ORDER BY 'year' (even though one row found, just to be identical query)
      # Trial 2 [retry==1]: Find match with 'eiin', 'board' - ORDER BY 'year'
      # Trial 3 [retry==0]: Find match with 'eiin' - ORDER BY 'year'
      $retry = 2; # retry two more times if institute name is not found for the given eiin,board,year

retry_inst_lookup:
      if ($result) {
	$result->free();
	$result = false;
      }

      $board_included = false;
      $extra = '';
      if ($row->eiin != '') {
	$institute_code = $row->eiin;
	$ikey = 'eiin';
      } else {
	$institute_code = $row->i_code;
	$ikey = 'i_code';
	$extra .= " AND board_name='$requested_board_upper'"; // in old age board names were not a problem, so using it when eiin is not found
	$board_included = true;
      }

      if ($retry >= 1) {
        if ($board_included == false) {
	  $extra .= " AND board_name='" . $requested_board_upper . "'";
	}
      }
      if ($retry >= 2) {
        $extra .= " AND year='" . $row->pass_year . "'";
      }

      if ($retry >= 0) {
	$extra .= " ORDER BY year"; # in case no institute found for the year, next retry should be with oldest year entry for inst
      }

      $sql = "SELECT i_name, centre FROM $institutes_table_name WHERE $ikey='$institute_code' $extra LIMIT 1";
      try {
	$result = $db->query($sql);
	if ($result && $result->num_rows > 0) {
	  $row1 = $result->fetch_object();
	  $row->inst_name = $row1->i_name;
	  $row->c_name = $row1->centre;
	} else {
	  $row->inst_name = "N/A";
	  $row->c_name = "N/A";
	  if ($retry >= 0) {
	    $retry--;
	    goto retry_inst_lookup;
	  }
	}
      } catch (Exception $e) {
	#echo "Exception [$sql]\n";
	#echo "Error: " . $db->error . "\n";
	$row->inst_name = '';
	$row->c_name = '';
      }

      if ($result) {
	$result->free();
	$result = false;
      }
      if (isset($env['REPLACE_EIIN'])) {
	if (!empty($row->inst_name)) {
	  $row->eiin = $row->inst_name;
	  unset($row->inst_name);
	}
      }
    }

    // we don't need the Database handle anymore
    $fnlist['pool']['put']([ 'app' => 'ebr', 'id' => 'mysqlpool', 'handle' => $db ]);
    $db = false;


    if (isset($row->marks_grd)) $row->marks_grd = str_replace("NULL", "", $row->marks_grd);
    if (isset($row->marks_grd1)) $row->marks_grd1 = str_replace("NULL", "", $row->marks_grd1);
    if (isset($row->marks_grd2)) $row->marks_grd2 = str_replace("NULL", "", $row->marks_grd2);

    if (isset($res_config['show_marks'])) {
      if (($res_config['show_marks'] == 1) || (isset($env['MARKS_BOARD']) && ($env['MARKS_BOARD'] == 'ANY' || $env['MARKS_BOARD'] == $requested_board))) {
	$EXAM_SHOW_MARKS = true;
      }
    }

    if ((isset($row->marks) && trim($row->marks) != "")
	|| (isset($row->marks_grd) && trim($row->marks_grd) != "")
	|| (isset($row->marks_grd1) && trim($row->marks_grd1) != "")
	|| (isset($row->marks_grd2) && trim($row->marks_grd2) != "")
	|| (isset($row->fth_mrk_gr) && trim($row->fth_mrk_gr) != "")
       ) {
      $MARKS_AVAILABLE = true;
    }

    $show_marks = 0;
    if (isset($MARKS_AVAILABLE) && isset($EXAM_SHOW_MARKS) && isset($REG_VERIFIED)) {
      $show_marks = 1;
    }

    if ($row->res_detail == "") {
      if ($row->gpa != "") $row->res_detail = "GPA=" . $row->gpa;
      else if ($row->result != "") $row->res_detail = $row->result;
    }

    $marks = "";
    $marks_ca = "";
    if ($show_marks == 1) {
      $show_marks = 0; # we are not sure if we can really show marks
      if (isset($row->marks) && trim($row->marks) != "") {
	$marks = $row->marks;
	$show_marks = 1;
	unset($row->marks);
      } else if ((isset($row->marks_grd) && trim($row->marks_grd) != "") ||
	(isset($row->marks_grd1) && trim($row->marks_grd1) != "") ||
	(isset($row->marks_grd2) && trim($row->marks_grd2) != "")) {
	@$x = trim($row->marks_grd);
	if ($x != "") {
	  $marks = $x;
	  $show_marks = 1;
	} else {
	  @$x = trim($row->marks_grd1);
	  if ($x != "") {
	    $marks = $x;
	    $show_marks = 1;
	  }
	  @$x = trim($row->marks_grd2);
	  if ($x != "") {
	    if ($marks != "") $marks .= ",";
	    $marks .= $x;
	    $show_marks = 1;
	  }
	}
	unset($row->marks_grd);
	unset($row->marks_grd1);
	unset($row->marks_grd2);
	if (isset($row->fth_mrk_gr) && trim($row->fth_mrk_gr) != "") {
	  $marks .= "," . $row->fth_mrk_gr;
	  unset($row->fth_mrk_gr);
	}

	if (isset($row->mrkgd_ca)) {
	  $marks_ca = $row->mrkgd_ca;
	  unset($row->mrkgd_ca);
	  #$show_marks = 0;
	} else if (isset($row->mrkgrd_ca)) {
	  #$show_marks = 0;
	  $marks_ca = $row->mrkgrd_ca;
	  unset($row->mrkgrd_ca);
	}

	# handling exceptoin, detailed marks in _ca not available, use letter grade _ca
	if ($marks_ca == "") {
	  if (isset($row->ltrgd_ca)) { # fallback to grade
	    $marks_ca = $row->ltrgd_ca;
	    #$show_marks = 0;
	  } else if (isset($row->ltrgrd_ca)) { # fallback to grade
	    #$show_marks = 0;
	    $marks_ca = $row->ltrgrd_ca;
	  }
	}
      } else {
	if (isset($row->ltrgd)) {
	  $marks = $row->ltrgd;
	  $show_marks = 0;
	} else if (isset($row->ltrgrd)) {
	  $marks = $row->ltrgrd;
	  $show_marks = 0;
	}
	if (isset($row->ltrgd_ca)) {
	  $marks_ca = $row->ltrgd_ca;
	} else if (isset($row->ltrgrd_ca)) {
	  $marks_ca = $row->ltrgrd_ca;
	}
      }
    } else {
      if (isset($row->marks_grd)) unset($row->marks_grd);
      if (isset($row->marks_grd1)) unset($row->marks_grd1);
      if (isset($row->marks_grd2)) unset($row->marks_grd2);
      if (isset($row->fth_mrk_gr)) unset($row->fth_mrk_gr);
      if (isset($row->tot_exc4th)) unset($row->tot_exc4th);
      if (isset($row->gtot)) unset($row->gtot);
      if (isset($row->mrkgd_ca)) unset($row->mrkgd_ca);
      if (isset($row->mrkgrd_ca)) unset($row->mrkgrd_ca);

      if (isset($row->ltrgd)) {
	$marks = $row->ltrgd;
	unset($row->ltrgd);
      } else if (isset($row->ltrgrd)) {
	$marks = $row->ltrgrd;
	unset($row->ltrgrd);
      }
      if (isset($row->ltrgd_ca)) {
	$marks_ca = $row->ltrgd_ca;
	unset($row->ltrgd_ca);
      } else if (isset($row->ltrgrd_ca)) {
	$marks_ca = $row->ltrgrd_ca;
	unset($row->ltrgrd_ca);
      }
    }
    unset($row->ltrgd);
    unset($row->ltrgrd);
    unset($row->ltrgd_ca);
    unset($row->ltrgrd_ca);

    $marks = trim(str_replace(",,", ",", $marks), " \t\r\n,");
    $row->display_details = $marks;
    unset($marks);
    $marks_ca = trim(str_replace(",,", ",", $marks_ca), " \t\r\n,");
    if ($marks_ca != "") {
      $row->display_details_ca = $marks_ca;
      unset($marks_ca);
    }

    /*
    # FOR TESTING LTRGRD_CA (JSC 17 RESULT)
    if (isset($_REQUEST['testca']) && !isset($row->display_details_ca)) { # only set if it is not already set
      $row->display_details_ca = $ltrgd_ca_test;
    }
     */

    # now finally check if we need to cut the broken down marks (config)
    if ($show_marks == 1 && isset($totalonly) && $totalonly == 1) {
      $row->display_details = preg_replace("/:[^=]+=/", ":", $row->display_details);
      if (isset($row->display_details_ca)) {
	$row->display_details_ca = preg_replace("/:[^=]+=/", ":", $row->display_details_ca);
      }
    } else {
      $totalonly = 0;
    }

    if (isset($res_config['remove_xxx']) && $res_config['remove_xxx'] == 1) {
      $row->display_details = str_replace("XXX", "   ", $row->display_details);
      if (isset($row->display_details_ca)) {
	$row->display_details_ca = str_replace("XXX", "   ", $row->display_details_ca);
      }
    }

    if (strpos($row->display_details, ':') !== false) {
      $USER_HAS_GRADE = true; # user is not absent or ltrgd is not empty
    } else {
      $row->display_details = ''; # nothing to show
    }

    $row->display_details = fix_grade_display($row->display_details); // fix malformed LTRGD/MARKS_GRD values
    if (isset($row->display_detatils_ca)) {
      $row->display_details_ca = fix_grade_display($row->display_details_ca); // fix malformed LTRGD/MARKS_GRD values
    }


    if ($show_marks == 1) $SHOW_MARKS = true;

    # show valuable message to the user
    #$row->env = json_encode($env);
    if (isset($env['HUMAN_DETECTED'])) {

      if (isset($USER_HAS_GRADE) && isset($EXAM_SHOW_MARKS) && isset($MARKS_AVAILABLE) && !isset($SHOW_MARKS)) {
	$robj->notice = "You may view result with marks by providing correct registration number.";
      } else if (isset($USER_HAS_GRADE) && isset($EXAM_SHOW_MARKS) && !isset($MARKS_AVAILABLE)) {
	$robj->notice = "Detailed Marks for $requested_board_upper Board is not available. Please visit " . $defs['board_website_map'][$requested_board] . " for details. Thanks. -Nixtec Systems";
      }

      if ($requested_result_type == '3' && isset($env['PERM']) && $env['PERM'] == '3') {
	if ($row->dob) $row->dob_text = dob2txt($row->dob);
	else $row->dob_text = "";

	// testimonial service
	$robj->template = file_get_contents("alltmpls/${requested_exam}_testimonial.html");
	$robj->template_vars = new stdClass();
	# inject vars into result data
	$row->year_postfix = substr($row->pass_year, -1);
	$row->center = $row->c_name;
	unset($row->c_name);
	$row->center = preg_replace("/ \(.*/", "", $row->center);

	# testimonial service, parentheses around name of center
	$xpattern = "/([A-Z]+ - [0-9]+,) ([A-Z\s]+)/i";
	$xreplace = '$1 ($2)'; # don't use double quotes
	$row->center = preg_replace($xpattern, $xreplace, $row->center);




	$row->print_date = date("d M, Y");
	$row->exam_name_show = $defs['exam_name_show_map'][$row->exam_name];
	switch ($row->sex) {
	case 0:
	  $row->stud_gender_son = "son";
	  $row->stud_gender_his = "his";
	  $row->stud_gender_he = "he";
	  $row->stud_gender_him = "him";
	  break;
	case 1:
	  $row->stud_gender_son = "daughter";
	  $row->stud_gender_his = "her";
	  $row->stud_gender_he = "she";
	  $row->stud_gender_him = "her";
	  break;
	default:
	  break;
	}
      } else {
	if (!isset($env['DISPLAY_REGNO']) && isset($res_config['hide_regno']) && $res_config['hide_regno'] == 1) $row->regno = "[NOT SHOWN]";
	if (!isset($env['DISPLAY_DOB']) && isset($res_config['hide_dob']) && $res_config['hide_dob'] == 1) $row->dob = "[NOT SHOWN]";
	if (!isset($env['DISPLAY_SESSION']) && isset($res_config['hide_session']) && $res_config['hide_session'] == 1) $row->session = "[NOT SHOWN]";
      }
    }

    if (isset($env['RESOLVE_SUBJECT'])) {


      #echo "Hello\n";

      $codes = get_codes_from_result($row->display_details . (isset($row->display_details_ca)? ',' . $row->display_details_ca : ''));
      #$codes_str = implode("_", $codes);

      $sub_board_default = 'dhaka'; # default board for subject mapping
      $sub_board_map = array("madrasah" => "madrasah", "tec" => "tec", "dibs" => "dibs");

      $sub_board = $sub_board_default; # Initialize with default board (dhaka)
      if (array_key_exists($requested_board, $sub_board_map)) {
	$sub_board = $sub_board_map[$requested_board];
      }

      #$fargs['key'] = 'common_defs.php';
      $fargs['key'] = "sub_config.php";
      $fargs['col'] = 'conf';
      $sub_config = [];
      list ($code, $data) = $fnlist['cache']['get']($fargs);
      if ($code == 200) {
	$sub_config = unserialize($data);
      }
      $sub_yyyy = $requested_year;
      if (isset($sub_config['sub_year'][$requested_exam][$sub_board])) {
	$sub_years = $sub_config['sub_year'][$requested_exam][$sub_board];
	$sub_yyyy = get_closest_sub_year($requested_year, $sub_years);
      }

      $sub_suffix  = "${sub_board}_${sub_yyyy}";

      # Now resolve subject mapping
      $ext = ".json";
      $subjects_file_array = [
	"${requested_exam}_sub_${sub_suffix}" . $ext, /* ssc_sub_dhaka_2016.json */
	"${requested_exam}_sub_${sub_board}" . $ext, /* ssc_sub_dhaka.json */
	"${requested_exam}_sub" . $ext, /* ssc_sub.json */
      ];
      #file_put_contents("/tmp/xxxx.txt", print_r($_REQUEST, true) . "\n", FILE_APPEND);
      $all_sub_codes = [];
      foreach ($subjects_file_array as $subjects_map_file) {
	$fargs['key'] = $subjects_map_file;
	$fargs['col'] = 'conf';
	list ($code, $data) = $fnlist['cache']['get']($fargs);
	if ($code == 200) {
	  $all_sub_codes = json_decode($data);
	  break;
	} else if ($show_marks == 1) {
	  $fargs['key'] = substr($subjects_map_file, 0, -strlen($ext)) . '.marks' . $ext;
	  list ($code, $data) = $fnlist['cache']['get']($fargs);
	  if ($code == 200) {
	    $all_sub_codes = json_decode($data);
	    break;
	  }
	}
      }


      #$robj->xxx = $codes;
      if ($codes) {
	$robj->sub_codes = $codes; # No more separate loading of Subject Codes through AJAX
	#$robj->sub_details = get_sub_tbl($subjects_map_file, $codes, "object"); # No more separate loading of Subject Codes through AJAX
	$robj->sub_details = get_sub_tbl_json($all_sub_codes, $codes); # No more separate loading of Subject Codes through AJAX
      }
    }



    $rcode = 200;
    $robj->status = 0;
    $robj->msg = "Success";
    $robj->res = $row;
    $robj->showmarks = $show_marks;
    $robj->totalonly = $totalonly;










    goto out;

group_result:
    #$robj->status = 0;
    #$robj->msg = "Success";
    #$robj->res = "Group Result";
    #$rcode = 200;

    $sendfile_prefix = $fnlist['ebr']['__cfg']['sendfile_prefix_pdf'];
    //$env['x-accel-redirect'] = $sendfile_prefix . '/' . $cinfo['index'] . '.' . $cinfo['file_ext'];

    $droot = $fnlist['ebr']['__cfg']['data_root_pdf'];
    $pdfviewer = $fnlist['ebr']['__cfg']['pdfviewer_file'] . '?file=';
    $domain = "@educationboard.gov.bd";
    $exam = $requested_exam;
    $year = $requested_year;
    $board = $requested_board;
    $eyb = "{$exam}/{$year}/{$board}";

    $headerfile = false;
    $resultfile = false;
    $pdffile = false;
    $titlefile = "{$droot}/{$eyb}/{$board}_title.txt";
    $is_sum_file = false;
    $is_stat_file = false;
    $has_stat_file = true;
    // last 5 years' comparison
    $ny = 5;
    $history_resultfile = [];

    switch ($requested_result_type) {
      case '2': // institution result
	#$requested_eiin = preg_replace("/[^0-9]+/", "", $_REQUEST['eiin'] ?? 0);
	$eiin = $requested_eiin;
	$headerfile = "{$droot}/{$eyb}/header/{$eiin}.txt";
	$resultfile = "{$droot}/{$eyb}/result/{$eiin}.txt";
	$sum_resultfile = "{$droot}/{$eyb}/result/sum_{$eiin}.txt";
	if (file_exists($sum_resultfile)) {
	  $resultfile = $sum_resultfile;
	  $is_sum_file = true;
	} else {
	  $resultfile = false;
	}
	$stat_resultfile = "{$droot}/{$eyb}/result/res_stat_{$eiin}.csv";
	$pdf_suffix = "{$eyb}/pdf/{$board}_{$exam}_{$year}_{$eiin}.pdf"; // Original PDF File
	$pdffile = "{$droot}/{$pdf_suffix}";
	$pdfpath = "{$sendfile_prefix}/{$pdf_suffix}"; // Aliased PDF Path (not directly accessible)
	$error_notfound = "No Result found for Exam/Year/Board/EIIN: {$eyb}/{$eiin}. Please check whether you entered EIIN correctly. If your institution does not have EIIN you can try providing Institution Code.";
	break;
      case '4': // center result
	#$requested_ccode = preg_replace("/[^0-9]+/", "", $_REQUEST['ccode'] ?? 0);
	$ccode = $requested_ccode;
	$resultfile = "{$droot}/{$eyb}/result/cent_{$ccode}.txt";
	$sum_resultfile = "{$droot}/${eyb}/result/cent_sum_${ccode}.txt";
	if (file_exists($sum_resultfile)) {
	  $resultfile = $sum_resultfile;
	  $is_sum_file = true;
	}
	$pdf_suffix = "{$eyb}/pdf/{$board}_{$exam}_{$year}_cent_{$ccode}.pdf"; // Original PDF File
	$pdffile = "${droot}/${pdf_suffix}";
	$pdfpath = "${sendfile_prefix}/${pdf_suffix}";
	$error_notfound = "No Result found for Exam/Year/Board/Centre Code: {$eyb}/{$ccode}.";
	break;
      case '5': // district result
	#$requested_dcode = preg_replace("/[^0-9]+/", "", $_REQUEST['dcode'] ?? 0);
	$dcode = $requested_dcode;
	$resultfile = "{$droot}/${eyb}/result/dist_{$dcode}.txt";
	$sum_resultfile = "{$droot}/${eyb}/result/dist_sum_{$dcode}.txt";
	if (file_exists($sum_resultfile)) {
	  $resultfile = $sum_resultfile;
	  $is_sum_file = true;
	}
	$pdf_suffix = "{$eyb}/pdf/{$board}_{$exam}_{$year}_dist_{$dcode}.pdf"; // Original PDF File
	$pdffile = "{$droot}/${pdf_suffix}";
	$pdfpath = "{$sendfile_prefix}/{$pdf_suffix}";
	$error_notfound = "No Result found for Exam/Year/Board/District Code: {$eyb}/{$dcode}.";
	break;
      case '6':
	// institution statistics Info
	#$requested_eiin = preg_replace("/[^0-9]+/", "", $_REQUEST['eiin'] ?? 0);
	$eiin = $requested_eiin;
	$headerfile = "{$droot}/{$eyb}/header/{$eiin}.txt";
	$resultfile = "{$droot}/{$eyb}/result/res_stat_{$eiin}.csv";
	$is_stat_file = true;
	$error_notfound = "No Result Analytics found for Exam/Year/Board/EIIN: {$eyb}/{$eiin}.";

	for ($i = 0; $i < $ny; $i++) {
	  $yr = $year - $i;
	  $hstat = "{$droot}/{$exam}/{$yr}/{$board}/result/res_stat_{$eiin}.csv";
	  if (file_exists($hstat)) {
	    $history_resultfile[$yr] = $hstat;
	  }
	}
	break;
      case '7':
	// this is set for tree view

	$_SESSION['btree'] = 'yes';
	$_SESSION['year'] = $year;
	$_SESSION['board'] = $board;
	$_SESSION['exam'] = $exam;

	// board statistics Info
	$resultfile = "{$droot}/{$eyb}/res_stat.csv";
	$is_stat_file = true;
	$error_notfound = "No Result Analytics found for Exam/Year/Board: {$eyb}.";
	for ($i = 0; $i < $ny; $i++) {
	  $yr = $year - $i;
	  $hstat = "{$droot}/{$exam}/{$yr}/{$board}/res_stat.csv";
	  if (file_exists($hstat)) {
	    $history_resultfile[$yr] = $hstat;
	  }
	}
	break;
      default:
	goto out;
	break;
    }

    // Group Result Code Here
    $authtype = "esif"; // use esif authentication (if required)
    /*
       if (!checkauth($eiin, $pass, $board, $exam, $authtype)) {
       $rcode = 403;
       $robj->status = 1;
       $robj->msg = "Authentication Failure";
       $robj->res = "";
       goto out;
       }
     */
    $pdfget = "pdl";
    $btree = "/app/stud/btree.html";

    $content = "";
    if ($titlefile && file_exists($titlefile)) $content .= "<center>" . nl2br(file_get_contents($titlefile)) . "</center><br/>";
    if ($headerfile && file_exists($headerfile)) $content .= nl2br(file_get_contents($headerfile)) . "<br/>";
    if ($resultfile && file_exists($resultfile)) {
      $res = trim(file_get_contents($resultfile));
      $charts = [];
      $charts_alt = [];
      $tot_app = $tot_pass = $tot_fail = $tot_gpa5 = $tot_gpa4 = $tot_gpa3 = $tot_gpa2 = $tot_gpa1 = 0;
      $tot_app_male = $tot_app_female = $tot_pass_male = $tot_pass_female = $tot_gpa5_male = $tot_gpa5_female = 0;
      if ($is_stat_file) {
	$have_alt = true;
	$notice = "<i><u>Note:</u> In Chart the fractional part in percentage calculation is rounded up to one digit after decimal point. So, 67.23 may become 67.2 and 39.68 may become 39.7. Percentage of GPA 5 is considered among passed students.<br/><u>Disclaimer:</u> This is Unofficial Analytics Report Automatically generated by computer program directly from raw result data provided by Education Board during result publication.<br/>.: Courtesy of Nixtec Systems :.</i>";
	$content .= "<center><h4><u>RESULT ANALYTICS</u></h4></center>";
	$content .= "<div class=\"alert alert-info text-center\" id=\"err_msg\">$notice</div>";
	$cinfos = csv2obj($res);
	foreach ($cinfos as $cinfo) {
	  if ($cinfo->gender == 'MALE') {
	    $tot_app_male += (int) $cinfo->app;
	    $tot_pass_male += (int) $cinfo->pass;
	    $tot_gpa5_male += (int) $cinfo->gpa5;
	  } else {
	    $tot_app_female += (int) $cinfo->app;
	    $tot_pass_female += (int) $cinfo->pass;
	    $tot_gpa5_female += (int) $cinfo->gpa5;
	  }
	  $tot_app += (int) $cinfo->app;
	  $tot_pass += (int) $cinfo->pass;
	  $tot_fail += (int) $cinfo->gpa0;
	  $tot_gpa5 += (int) $cinfo->gpa5;
	  $tot_gpa4 += (int) $cinfo->gpa4;
	  $tot_gpa3 += (int) $cinfo->gpa3;
	  $tot_gpa2 += (int) $cinfo->gpa2;
	  $tot_gpa1 += (int) $cinfo->gpa1;
	}
	if ($tot_app == 0) {
	  $percent_pass = "N/A";
	} else {
	  $percent_pass = round(($tot_pass/$tot_app)*100, 2);
	}
	$notice = "<b><u>Statement:</u></b> Total $tot_app ($tot_app_male male and $tot_app_female female) students appeared. Among them $tot_pass ($tot_pass_male male and $tot_pass_female female) students passed, i.e. secured minimum GP 1.0 in every compulsory and elective subject. Percentage of Pass is $percent_pass. Total $tot_gpa5 ($tot_gpa5_male male and $tot_gpa5_female female) students secured GPA 5.00.";
	$content .= "<div class=\"alert alert-success text-center\" id=\"stmt\">$notice</div>";

	$obj = new stdClass();
	$obj->id = "pass_fail_pie";
	$obj->type = "pie";
	$obj->attr = "3d";
	$obj->title = "Passed vs Not Passed [Year: $year] (Total Appeared: $tot_app)";
	$obj->data[] = ["Type", "Count"];
	$obj->data[] = ["Passed ($tot_pass)", $tot_pass];
	$obj->data[] = ["Not Passed ($tot_fail)", $tot_fail];
	$charts[] = $obj;
	if ($have_alt) $charts_alt[] = $obj;

	$obj = new stdClass();
	$obj->id = "gpa_pass_pie";
	$obj->type = "pie";
	$obj->attr = "3d";
	$obj->title = "GPA Countdown [Year: $year] (Total Passed: $tot_pass)";
	$obj->data[] = ["Type", "Count"];
	$obj->data[] = ["GPA 5.00 ($tot_gpa5)", $tot_gpa5];
	$obj->data[] = ["GPA 4.x ($tot_gpa4)", $tot_gpa4];
	$obj->data[] = ["GPA 3.x ($tot_gpa3)", $tot_gpa3];
	$obj->data[] = ["GPA 2.x ($tot_gpa2)", $tot_gpa2];
	$obj->data[] = ["GPA 1.x ($tot_gpa1)", $tot_gpa1];
	$charts[] = $obj;
	$charts_alt[] = $obj;

	if (count($history_resultfile) > 1) {
	  $yrs = [];
	  $gnd = [];
	}
	foreach ($history_resultfile as $yr => $resultfile) {
	  $yrs[] = $yr;
	  $res = trim(file_get_contents($resultfile));
	  $cinfos = csv2obj($res);
	  foreach ($cinfos as $cinfo) {
	    $gnd[$yr][] = $cinfo->gender;
	    $sex = $cinfo->gender;
	    $app[$yr][$sex] = (int) $cinfo->app;
	    $pass[$yr][$sex] = (int) $cinfo->pass;
	    $fail[$yr][$sex] = (int) $cinfo->gpa0;
	    $gpa5[$yr][$sex] = (int) $cinfo->gpa5;
	    $gpa4[$yr][$sex] = (int) $cinfo->gpa4;
	    $gpa3[$yr][$sex] = (int) $cinfo->gpa3;
	    $gpa2[$yr][$sex] = (int) $cinfo->gpa2;
	    $gpa1[$yr][$sex] = (int) $cinfo->gpa1;
	  }
	}

	$obj = new stdClass();
	$obj->id = "pass_fail_history";
	$obj->type = "bar";
	$obj->attr = "stacked";
	$obj->title = "[History] Passed vs Not Passed (Among Appeared)";

	if ($have_alt) {
	  $alt_obj = new stdClass();
	  $alt_obj->id = $obj->id;
	  $alt_obj->title = $obj->title . " (% of GPA 5 among passed)";
	  $alt_obj->type = "table";
	  $alt_obj->data[] = ["Year", "Appeared", "Passed", "Not Passed", "% of Pass", "GPA 5", "% of GPA 5"];
	}
	$tmp_data = ["Year", "Passed", "Not Passed"];
	$obj->data[] = $tmp_data;

	foreach ($yrs as $yr) {
	  $tot_app = $tot_pass = $tot_fail = $percent_pass = $tot_gpa5 = $percent_gpa5 = 0;
	  foreach ($gnd[$yr] as $sex) {
	    $tot_pass += $pass[$yr][$sex];
	    $tot_fail += $fail[$yr][$sex];
	    if ($have_alt) {
	      $tot_app += $app[$yr][$sex];
	      $tot_gpa5 += $gpa5[$yr][$sex];
	    }
	  }
	  $tmp_data = ["$yr", $tot_pass, $tot_fail];
	  $obj->data[] = $tmp_data;
	  if ($have_alt) {
	    if ($tot_app == 0) {
	      $percent_pass = "N/A";
	    } else {
	      $percent_pass = round(($tot_pass/$tot_app)*100, 2);
	    }
	    if ($tot_pass == 0) {
	      $percent_gpa5 = "N/A";
	    } else {
	      $percent_gpa5 = round(($tot_gpa5/$tot_pass)*100, 2);
	    }
	    $tmp_data = ["$yr", $tot_app, $tot_pass, $tot_fail, $percent_pass, $tot_gpa5, $percent_gpa5];
	    $alt_obj->data[] = $tmp_data;
	  }
	}
	$charts[] = $obj;
	if ($have_alt) {
	  $charts_alt[] = $alt_obj;
	}
	$obj = new stdClass();
	$obj->id = "gpa_pass_history";
	$obj->type = "bar";
	$obj->attr = "stacked";
	$obj->title = "[History] GPA Countdown (Among Passed)";
	$obj->data[] = ["Year", "GPA 5.00", "GPA 4.x", "GPA 3.x", "GPA 2.x", "GPA 1.x"];

	foreach ($yrs as $yr) {
	  $tot_gpa5 = $tot_gpa4 = $tot_gpa3 = $tot_gpa2 = $tot_gpa1 = 0;
	  foreach ($gnd[$yr] as $sex) {
	    $tot_gpa5 += $gpa5[$yr][$sex];
	    $tot_gpa4 += $gpa4[$yr][$sex];
	    $tot_gpa3 += $gpa3[$yr][$sex];
	    $tot_gpa2 += $gpa2[$yr][$sex];
	    $tot_gpa1 += $gpa1[$yr][$sex];
	  }
	  $obj->data[] = ["$yr", $tot_gpa5, $tot_gpa4, $tot_gpa3, $tot_gpa2, $tot_gpa1];
	}
	$charts[] = $obj;
	if ($have_alt) {
	  $alt_obj = new stdClass();
	  $alt_obj->id = $obj->id;
	  $alt_obj->type = "table";
	  $alt_obj->title = $obj->title;
	  $alt_obj->data = $obj->data;
	  $charts_alt[] = $alt_obj;
	}
      } else {
	$_SESSION['pdf_path'] = $pdfpath;
	$res = str_replace(">\n", "<br/>", $res);
	if ($is_sum_file) {
	  $res = preg_replace("/>([A-Za-z0-9\.& -]+)\n/", "<br/><font color=red>\\1</font>: ", $res);
	  $res = preg_replace("/=([0-9]+)\n/", "=\\1; ", $res);
	  $content .= "<center><h4><u>RESULT SUMMARY</u></h4></center>";
	  if ($has_stat_file) {
	    $notice = "*** <i>For Result Analytics, please search again with '<u>Analytics</u>' Result Type</i> ***";
	    $content .= "<div class=\"alert alert-info text-center\" id=\"err_msg\">$notice</div>";
	  }
	} else {
	  $res = preg_replace("/>([A-Za-z0-9\.& -]+)\n/", "<br/><center><font color=red>\\1</font></center>", $res);
	  $res = preg_replace("/=([0-9]+)\n/", "=\\1<br/>", $res);
	}
	$res = preg_replace("/\+([^\n]+)\n/", "<br/><br/><font color=blue>\\1</font>", $res);
	$content .= $res;
      }

      $extra = new stdClass();
      $extra->content = $content;
      if ($is_stat_file) {
	$extra->charts = $charts;
	$extra->have_alt = 0;
	if ($have_alt) {
	  $extra->have_alt = 1;
	  $extra->charts_alt = $charts_alt;
	}
      }
      if (isset($_SESSION['btree']) && $_SESSION['btree'] == true) {
	$extra->tree = $btree;
      }

      if ($pdffile && file_exists($pdffile)) {
	$extra->pdfname = basename($pdffile);
	$extra->download = $pdfget;
	#$extra->view = "${pdfviewer}$pdfget";
	$extra->view = "${pdfviewer}"; # append '/prefix' $extra->download by javascript
      }

      $rcode = 200;
      $robj->status = 0;
      $robj->msg = "Success";
      $robj->extra = $extra;

    } else {

      $rcode = 404;
      $robj->status = 1;
      $robj->msg = $error_notfound;

    }


    goto out;

out:
    if (isset($db) && $db != false) {
      $fnlist['pool']['put']([ 'app' => 'ebr', 'id' => 'mysqlpool', 'handle' => $db ]);
      $db = false;
    }
    return [ $rcode, json_encode($robj) ];

  },

  'stat' => function (&$args=null) use (&$fnlist) {

    $env = &$args['env'];
    $_REQUEST = $args['reqargs'];
    $curtime = $env['server']['TIME'] ?? time();

    list ($code, $data) = $fnlist['cache']['dump']([ 'app' => 'ebr', 'id' => 'counter' ]);
    #print_r($data);

    $cexam = [];
    $cyear = [];
    $cboard = [];
    $crtype = [];

    $stime = $curtime;

    foreach ($data as $cinfo) {
      list ($key, $cdata) = $cinfo;
      if ($key == '0/0/0/0') {
        $stime = $cdata['cnt'];
	continue;
      }
      list ($exam, $year, $board, $rtype) = explode('/', $key);
      $cnt = $cdata['cnt'];
      if (!isset($_REQUEST['exam']) || $_REQUEST['exam'] == $exam) {
        if (!isset($cexam[$exam])) $cexam[$exam] = 0;
        $cexam[$exam] += $cnt;
      }
      if (!isset($_REQUEST['year']) || $_REQUEST['year'] == $year) {
        if (!isset($cyear[$year])) $cyear[$year] = 0;
        $cyear[$year] += $cnt;
      }
      if (!isset($_REQUEST['board']) || $_REQUEST['board'] == $board) {
        if (!isset($cboard[$board])) $cboard[$board] = 0;
        $cboard[$board] += $cnt;
      }
      if (!isset($_REQUEST['rtype']) || $_REQUEST['rtype'] == $rtype) {
        if (!isset($crtype[$rtype])) $crtype[$rtype] = 0;
        $crtype[$rtype] += $cnt;
      }
    }

    $fargs = ['app' => 'ebr', 'id' => 'resconfig' ];
    $fargs['key'] = 'common_defs.php';
    $fargs['col'] = 'conf';
    list ($dcode, $data) = $fnlist['cache']['get']($fargs);
    $defs = unserialize($data);


    #$rdata = [ 'time' => $curtime, 'defs' => [ 'rtype' => $defs['result_type'], 'board' => $defs['board_map'], 'exam' => $defs['exam_map'] ], 'exams' => $cexam, 'years' => $cyear, 'boards' => $cboard, 'rtypes' => $crtype ];
    #print_r($rdata);

    $statobj = new stdClass();
    $curdate = date("r", $curtime);
    $statobj->header = "Generated on: $curdate";
    $statobj->title_res = "Result Statistics\nFrom: " . date("r", $stime) . "\nTo: " . $curdate;
    $statobj->footer = "Powered by Nixtec Systems";
    $stat_res = [];
    $stat_res[] = [ 'Service', 'Served' ];
    foreach ($crtype as $k => $v) {
      if (isset($defs['result_type'][$k])) {
	$stat_res[] = [ $defs['result_type'][$k], $v ];
      }
    }
    $statobj->stat_res = $stat_res;

    #print_r($statobj);

    $rcode = $code;
    return [ $code, json_encode($statobj) ];
  },

];

?>
