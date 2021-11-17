<?php

#require('noswoole.php');

#header("Content-type: application/json");

$fs = "|";
$rs = "\n";
#$boards = explode(" ", "barisal chittagong comilla dhaka dibs dinajpur jessore madrasah rajshahi sylhet technical");
#$boards = explode(" ", "dhaka");

#$board = preg_replace("/[^a-z]+/", "", $_REQUEST['board']);
#$exam = preg_replace("/[^a-z]+/", "", $_REQUEST['exam']);
#$year = preg_replace("/[^0-9]+/", "", $_REQUEST['year']);

$bobj = new stdClass();

$result = false;
$success = false;
if ($cid == 'btree_session') $result = true; # show result for authenticated user (from session)
if (isset($_REQUEST['board']) && isset($_REQUEST['exam']) && isset($_REQUEST['year'])) {
  $board = preg_replace("/[^a-z]+/", "", $_REQUEST['board']);
  $exam = preg_replace("/[^a-z]+/", "", $_REQUEST['exam']);
  $year = preg_replace("/[^0-9]+/", "", $_REQUEST['year']);

  if ($exam == "dibs") {
    $board = "dibs";
    $exam = "hsc";
  }
} else {
  #session_start();
  if (!isset($_SESSION['btree']) || $_SESSION['btree'] == false) {
    $bobj = new stdClass();
    goto the_end_btree;
    #die();
  }

  $board = $_SESSION['board'];
  $exam = $_SESSION['exam'];
  $year = $_SESSION['year'];
}

if ($board == "" || $exam == "" || $year == "") {
  goto the_end_btree;
  #die();
}

$file = "../inst/pdf/${exam}/${year}/${board}/${exam}_${board}_${year}_inst.csv";
if (!file_exists($file)) {
  $bobj = new stdClass();
  goto the_end_btree;
  #die(json_encode($bobj));
}
$contents = file_get_contents($file);
$lines = explode($rs, $contents);
unset($contents); # free resources


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
    # parse header
    for ($i = 0; $i < $nf; $i++) {
      $k = $f[$i];
      $kmap[$k] = $i;
    }
    continue; # no need to go down
  }


  if ($istbl) {
    $cols = array();
    foreach ($tbl_cols as $k) {
      $cols[] = $f[$kmap[$k]];
    }
    $tbl_rows[] = $cols;
    continue; # no need to go down, csv is done
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

  # technical board didn't provide zcode and tcode, handle it
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
    #$e_title[$eiin] = "Total Appeared: $app, Total Passed: $pass, Percentage of Pass: $percent, GPA 5: $gpa5";
    $e_info[$eiin] = $info;
  }
  $eiin_t_list[$zcode][$tcode][] = $eiin;
}

if ($istbl) {
  $bobj = new stdClass();
  $bobj->data = $tbl_rows;

  $success = true;
  goto the_end_btree;

  #echo json_encode($bobj);
  #die();
}

foreach ($eiin_t_list as $zcode => $x) {
  $zobj = new stdClass();
  $zobj->name = $z_map[$zcode];

  # Dhaka is divided into two regions, but in the result Database both are 'DHAKA MAHANAGARI'. So we're going to add suffix to the second one
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
	#$eobj->title = $e_title[$eiin];
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
$success = true;

the_end_btree:
$__res = json_encode($bobj);

if ($success) {
  $server->cache[$CACHE_ID]->set($CACHE_KEY, [ $CACHE_KEY1 => $__res, '__ts' => time() ]);
}

?>
