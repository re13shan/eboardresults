<?php


function getres_impl_c($env=null)
{
  if ($env == null) {
    $env = array();
    $env['request'] = array();
    $env['session'] = array();
    $env['defs'] = array();
    $env['server'] = array();
  }

  $_REQUEST = &$env['request'];
  $_SESSION = &$env['session'];
  $defs = &$env['defs'];
  $server = &$env['server'];




  # Individual Result continues here




  /* override configuration as in this order */
  $config_file_array = array(
    "${requested_exam}_config.php",
    "${requested_exam}_${requested_year}_config.php",
    "${requested_board}_config.php",
    "${requested_board}_${requested_exam}_config.php",
    "${requested_exam}_${requested_board}_${requested_year}_config.php",
  );

  # one config overrides previous ones
  foreach ($config_file_array as $config_file) {
    if ($config_file && file_exists($config_file)) {
      require($config_file);
      #$pret->cfiles .= $config_file . ":show_marks=$show_marks:,"; # debugging
    }
  }



  /*
  if ($_SERVER['REMOTE_ADDR'] == '103.197.206.71') {
    $show_marks = 1; # just for us
  }
  */


  if (isset($env['CHECK_ENLISTED'])) {
    if (isset($env['HUMAN_DETECTED']) && !in_array($requested_board, $defs['board_enlisted'])) {
      $pret->status = 1;
      $pret->msg = strtoupper($defs['board_map'][$requested_board]) . " BOARD IS NOT ENLISTED. PLEASE VISIT WEBSITE FOR DETAILS: " . $defs['board_website_map'][$requested_board];
      goto the_end;
      #echo json_encode($pret);
      #die();
    }
  }





  /*
  if ($requested_roll != "123456") {
    die("SSC 2016 Result will be available after 2:00 PM");
  }
   */


  #$result_title = "Result of " . strtoupper($requested_exam) . " or Equivalent Examination - $requested_year";


  $suffix  = "${requested_board}_${requested_year}";
  /*
  if ($requested_roll != '123456') {
    #die("$result_title Will be available at 2:00 PM on 31st December");
    die("$result_title Will be available Soon");
  }
   */

  $result_table_name = $requested_exam . "_${suffix}";


  #sleep(3);
  #$db_host = "p:localhost"; // try to use persistent connections
  #$db_host = "127.0.0.1";
  #$subjects_table_name = "${requested_exam}_sub_${suffix}";
  #$institutes_table_name = "${requested_exam}_inst_${suffix}";
  #$institutes_table_name = "${requested_exam}_inst_${requested_board}_2016"; # Need to fix this to handle it better way
  $institutes_table_name = "${requested_exam}institute"; # Need to fix this to handle it better way

  $use_apc = false;
  $enable_details = true;
  $enable_showmarks = true;
  $enable_showtotal = true;


  $db = false;


  /*
  if (isset($db->reuse)) {
    if ($db->reuse) {
      echo "Reused\n";
    } else {
      echo "Not Reused\n";
    }
  } else {
    echo "reuse is not set\n";
  }
  */

  if (isset($_REQUEST['type'])) {
    $extra = "";
    $qtype = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $_REQUEST['type']));
    switch ($qtype) {
    case 'inst':
      if (isset($_REQUEST['eiin']) && $_REQUEST['eiin'] != "") {
	$ikey = "eiin";
	#$extra = "ORDER BY year DESC";
      } else {
	$ikey = "i_code";
	if (!isset($_REQUEST['board']) || $_REQUEST['board'] == "") {
	  $pret->status = 1;
	  $pret->msg = "You must provide board when you specify institute code";
	  goto the_end;
	  #die(json_encode($pret));
	}
	#$extra = "AND board_name='$requested_board_upper' ORDER BY year DESC";
	$extra = "AND board_name='$requested_board_upper'";
	#$extra = "";
      }

      if ($requested_year != "") {
	$extra .= "AND year='$requested_year'";
      } else {
	$extra .= " ORDER BY year DESC";
      }
      $institute_code = preg_replace("/[^0-9]+/", "", $_REQUEST[$ikey]);


      #echo $ikey . "," . $institutes_table_name . "," . $institute_code . "," . $extra . "\n";
      #$einfo = get_code_names($db, $institutes_table_name, $ikey, "*", $institute_code, "ERROR:", $extra);
      $sql = "SELECT * FROM $institutes_table_name WHERE $ikey='$institute_code' $extra LIMIT 1";
      if (!$db) { $db = $server->mp->get(); if (!$db) { $pret->status = 1; $pret->msg = "Error connecting to database. Please contact support."; goto the_end; } }
      try {
        $result = $db->query($sql);
      } catch (Exception $e) {
        $result = false;
      }
      $einfo = false;
      if ($result) {
	$einfo = $result->fetch_object();
      }
      if (is_array($einfo)) {
	#print_r($einfo);
	$pret->status = 0;
	$pret->msg = "Success";
	$pret->res = $einfo;
      } else {
	$pret->status = 1;
	$pret->msg = "Requested information is not found.";
      }
      goto the_end;
      #die(json_encode($pret));
      break;
    default:
      break;
    }
  }

  $nrows = 0;
  $rows = array();

  #$query = "SELECT * FROM `$result_table_name` WHERE `roll_no`='$requested_roll' AND board_name='$requested_board_upper' LIMIT 1";
  $sql = "SELECT * FROM $result_table_name WHERE roll_no='$requested_roll'"; # 'LIMIT 1' is removed because Technical Board result may give multiple result for same roll, in those cases we need to check registration number as well
  if (!$db) { $db = $server->mp->get(); if (!$db) { $pret->status = 1; $pret->msg = "Error connecting to database. Please contact support."; goto the_end; } }
  try {
    $result = $db->query($sql);
  } catch (Exception $e) {
    $result = false;
  }
  #file_put_contents('/tmp/xxyy.txt', "$query : Error Value: " . mysqli_error($con) . " [" . mysqli_errno($con) . "]\n", FILE_APPEND);
  if ($result) {
    #$nrows = $result->num_rows;
    while ($row = $result->fetch_object()) {
      #print_r($row);
      $rows[] = $row;
    }
  }

  $nrows = count($rows);

  if ($nrows > 0) {

    if ($nrows > 1 && $requested_reg == "") {
      $pret->status = 1; 
      $pret->msg = "Registration Number is Needed due to having multiple record with same roll number.";
      $pret->res = "";
      goto the_end;
      #die(json_encode($pret));
    }



    $oldrow = $row = false;
    foreach ($rows as $row) {
      $oldrow = $row;
      if ($requested_reg && $row->regno == $requested_reg) break;
    }

    $row = $oldrow; # Make it object, for easy access (using members)
    if ($result) { $result->free(); $result = false; }


    if ($requested_result_type == "3") {
      if (isset($_SESSION['login_eiin']) && $_SESSION['login_eiin'] != $row->eiin) {
	$pret->status = 1; 
	$pret->msg = "You are not allowed to access this data";
	$pret->res = "";
	goto the_end;
	#die(json_encode($pret));
      }
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

    $extra = "";
    if ($row->eiin != "") {
      $institute_code = $row->eiin;
      $ikey = 'eiin';
      #$extra = "AND board_name='$requested_board_upper' ORDER BY year DESC";
      #$extra = " ORDER BY year DESC"; # due to board's name change recently, we are not including board's name in query
      #$extra = "ORDER BY year DESC";
    } else {
      $institute_code = $row->i_code;
      $ikey = 'i_code';
      $extra = "AND board_name='$requested_board_upper'"; # in old age board names were not a problem, so using it when eiin is not found
      #$extra = " ORDER BY year DESC";
    }
    $extra .= " ORDER BY year DESC";

    if (isset($env['RESOLVE_EIIN'])) {
      #$row->inst_name = get_code_names($con, $institutes_table_name, $ikey, "i_name", $institute_code, "Institution with EIIN/CODE", $extra);
      $sql = "SELECT i_name FROM $institutes_table_name WHERE $ikey='$institute_code' $extra LIMIT 1";
      if (!$db) { $db = $server->mp->get(); if (!$db) { $pret->status = 1; $pret->msg = "Error connecting to database. Please contact support."; goto the_end; } }
      try {
        $result = $db->query($sql);
	if ($result && $result->num_rows > 0) {
	  $row1 = $result->fetch_object();
	  $row->inst_name = $row1->i_name;
	} else {
	  #echo http_build_query($_REQUEST) . "\n";
	  #echo "** No Inst Name for query: $sql\n";
	  $row->inst_name = "N/A";
	}
      } catch (Exception $e) {
        $result = false;
	$row->inst_name = '';
      }
      if ($result) { $result->free(); $result = false; }
      if (isset($env['REPLACE_EIIN'])) {
        if (!empty($row->inst_name)) {
	  $row->eiin = $row->inst_name;
	  unset($row->inst_name);
	}
      }
    }

    if ($requested_cname && $row->c_code > 0) {
      $extra_old = $extra;
      $search_cols = array(
	"year" => $row->pass_year,
	//"board_name" => $row->board_name,
	"c_code" => $row->c_code
      );
      $extra = "";
      #$row->c_name = get_code_names($con, $institutes_table_name, $search_cols, "centre", $row->c_code, "Center with CODE", $extra);
      if (!$db) { $db = $server->mp->get(); if (!$db) { $pret->status = 1; $pret->msg = "Error connecting to database. Please contact support."; goto the_end; } }
      $sql = "SELECT centre  FROM $institutes_table_name WHERE " . http_build_query($search_cols, '', ',') . " $extra LIMIT 1";
      try {
        $result = $db->query($sql);
        $row1 = $result->fetch_object();
        $row->c_name = $row1->centre;
      } catch (Exception $e) {
        $result = false;
	$row->c_name = '';
      }
      if ($result) { $result->free(); $result = false; }

      #$row->c_name = preg_replace("/ \(.*/", "", $row->c_name);
      $extra = $extra_old; # restore old value
    }

    $reg_required = false;
    if ($requested_reg != "") {
      $reg_required = true;
    }

    $reg_ok = false;
    if ($reg_required == true
      && ($requested_reg == $row->regno || (isset($regno_match_partial) && $regno_match_partial == true && substr_compare($row->regno, $requested_reg, 0, strlen($row->regno)) == 0)))
    {
      $REG_VERIFIED = true;
      $reg_ok = true;
    }

    if ($reg_required == true && $reg_ok == false) {
      $pret->msg = "Incorrect registration number";
      goto the_end;
      #die(json_encode($pret));
    }

    /* handle 'NULL' string */
    if (isset($row->marks_grd)) $row->marks_grd = str_replace("NULL", "", $row->marks_grd);
    if (isset($row->marks_grd1)) $row->marks_grd1 = str_replace("NULL", "", $row->marks_grd1);
    if (isset($row->marks_grd2)) $row->marks_grd2 = str_replace("NULL", "", $row->marks_grd2);

    if (isset($show_marks)) {
      if (($show_marks == 1) || (isset($_SESSION['marks_board']) && ($_SESSION['marks_board'] == 'ANY' || $_SESSION['marks_board'] == $requested_board))) {
	$EXAM_SHOW_MARKS = true;
      }
    }

    if ((isset($row->marks) && trim($row->marks) != "")
      || (isset($row->marks_grd) && trim($row->marks_grd) != "")
      || (isset($row->marks_grd1) && trim($row->marks_grd1) != "")
      || (isset($row->marks_grd2) && trim($row->marks_grd2) != "")
      || (isset($row->fth_mrk_gr) && trim($row->fth_mrk_gr) != "")
    ) $MARKS_AVAILABLE = true;


    if (isset($MARKS_AVAILABLE) && isset($EXAM_SHOW_MARKS) && isset($REG_VERIFIED)) {
      $show_marks = 1;
    } else {
      $show_marks = 0;
    }



  #  else {
  #    $show_marks = 0;
  #    $row->regno = "[NOT SHOWN]";
  #  }

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
	/*
	else if (isset($row->ltrgd_ca)) { # fallback to grade
	  $marks_ca = $row->ltrgd_ca;
	  #$show_marks = 0;
	} else if (isset($row->ltrgrd_ca)) { # fallback to grade
	  #$show_marks = 0;
	  $marks_ca = $row->ltrgrd_ca;
	}
	*/
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
	  #$show_marks = 0;
	} else if (isset($row->ltrgrd_ca)) {
	  #$show_marks = 0;
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

    if (isset($remove_xxx) && $remove_xxx == 1) {
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
	$pret->notice = "You may view result with marks by providing correct registration number.";
      } else if (isset($USER_HAS_GRADE) && isset($EXAM_SHOW_MARKS) && !isset($MARKS_AVAILABLE)) {
	$pret->notice = "Detailed Marks for $requested_board_upper Board is not available. Please visit " . $defs['board_website_map'][$requested_board] . " for details. Thanks. -Nixtec Systems";
      }

      if (isset($env['NIXTEC_VERIFIED']) && $_SESSION['perm'] == '3') {
	if ($row->dob) $row->dob_text = dob2txt($row->dob);
	else $row->dob_text = "";

	// testimonial service
	$pret->template = file_get_contents("alltmpls/${requested_exam}_testimonial.html");
	$pret->template_vars = new stdClass();
	# inject vars into result data
	$row->year_postfix = substr($row->pass_year, -1);
	$row->center = "";
	$search_cols = array(
	  "year" => $row->pass_year,
	  //"board_name" => $row->board_name,
	  "c_code" => $row->c_code
	);
	#$row->center = get_code_names($con, $institutes_table_name, $search_cols, "centre", $row->c_code, "Center with CODE", $extra);
	$sql = "SELECT centre  FROM $institutes_table_name WHERE " . http_build_query($search_cols, '', ',') . " $extra LIMIT 1";
	if (!$db) { $db = $server->mp->get(); if (!$db) { $pret->status = 1; $pret->msg = "Error connecting to database. Please contact support."; goto the_end; } }
	try {
	  $result = $db->query($sql);
	  $row1 = $result->fetch_object();
	  $row->center = $row1->centre;
	} catch (Exception $e) {
	  $result = false;
	  $row->center = '';
	}
	if ($result) { $result->free(); $result = false; }

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
	if (isset($hide_regno) && $hide_regno == 1) $row->regno = "[NOT SHOWN]";
	if (isset($hide_dob) && $hide_dob == 1) $row->dob = "[NOT SHOWN]";
	if (isset($hide_session) && $hide_session == 1) $row->session = "[NOT SHOWN]";
      }
    }

    if (isset($env['RESOLVE_SUBJECT'])) {


      #echo "Hello\n";

      $codes = get_codes_from_result($row->display_details . (isset($row->display_details_ca)? ',' . $row->display_details_ca : ''));
      #$codes_str = implode("_", $codes);
      #file_put_contents("/tmp/zzz.txt", $codes . "\n", FILE_APPEND);

      $sub_board_default = 'dhaka'; # default board for subject mapping
      $sub_board_map = array("madrasah" => "madrasah", "tec" => "tec", "dibs" => "dibs");

      $sub_board = $sub_board_default; # Initialize with default board (dhaka)
      if (array_key_exists($requested_board, $sub_board_map)) {
	$sub_board = $sub_board_map[$requested_board];
      }

      $sub_config = "sub_config.php";
      $sub_yyyy = $requested_year;
      if (file_exists($sub_config)) {
	require($sub_config);
	$sub_years = $sub_year[$requested_exam][$sub_board];
	$sub_yyyy = get_closest_sub_year($requested_year, $sub_years);
	#file_put_contents("/tmp/xxxx.txt", "Requested year: $requested_year, exam: $requested_exam, sub_board: $sub_board, sub_yyyy=$sub_yyyy" . "\n", FILE_APPEND);
	#file_put_contents("/tmp/xxxx.txt", $requested_exam . ":" . $sub_board . "\n" . print_r($sub_years, true) . "\n", FILE_APPEND);
      }




      $sub_suffix  = "${sub_board}_${sub_yyyy}";

      # Now resolve subject mapping
      $ext = ".json";
      $subjects_file_array = array(
	"${requested_exam}_sub_${sub_suffix}" . $ext, /* ssc_sub_dhaka_2016.json */
	"${requested_exam}_sub_${sub_board}" . $ext, /* ssc_sub_dhaka.json */
	"${requested_exam}_sub" . $ext, /* ssc_sub.json */
	false
      );
      $subjects_map_file = false;
      #file_put_contents("/tmp/xxxx.txt", print_r($_REQUEST, true) . "\n", FILE_APPEND);
      foreach ($subjects_file_array as $subjects_map_file) {

	#file_put_contents("/tmp/xxxx.txt", $subjects_map_file . "\n", FILE_APPEND);
	if ($subjects_map_file && file_exists($subjects_map_file)) break;
      }

      # check if equivalent subject code file for Marks is available
      if ($show_marks == 1 && $subjects_map_file) {
	$marks_sub_file = substr($subjects_map_file, 0, -strlen($ext)) . ".marks" . $ext;
	if (file_exists($marks_sub_file)) {
	  $subjects_map_file = $marks_sub_file;
	}
      }


      #$pret->xxx = $codes;
      if ($codes) {
	$pret->sub_codes = $codes; # No more separate loading of Subject Codes through AJAX
	$pret->sub_details = get_sub_tbl($subjects_map_file, $codes, "object"); # No more separate loading of Subject Codes through AJAX
      }
    }



    $pret->status = 0;
    $pret->msg = "Success";
    $pret->res = $row;
    $pret->showmarks = $show_marks;
    $pret->totalonly = $totalonly;


    #$pret->notice = "Testing"; # testing microphone
    #$pret->eiin_url = "testeiin.php"; # to test success
    #$pret->eiin_url = "eiintest.php"; # to test error

    #$res = json_encode($pret);
    goto the_end;
  } else {

    $pret->status = 1;
    $pret->msg = "Result of your specified criteria is not found. Please check and try again.";
    $pret->res = "";
    if (isset($env['API_DETECTED'])) {
      if ($db->errno == 1146) { # 1146 = Table or View doesn't exist
	$pret->status = 2;
	$pret->msg = $db->error;
      }
    }
    goto the_end;

  #  $res = json_encode($pret);
  }
  #$db->close(); # it doesn't close persistent connections actually, but it's safe to call it anyway, since we might not know whether we're using persistent connections or not

  the_end:
  if ($db) {
    #echo "Putting back to Connection Pool\n";
    $server->mp->put($db);
  }
  $res = json_encode($pret);
  return $res;
}

?>
