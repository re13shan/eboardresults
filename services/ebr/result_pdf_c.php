<?php

if (!isset($env['CAN_VIEW_RESULT'])) goto the_end_pdf;

$sapi = "nginx";
$sendfile_prefix = "/result/inst/pdf";

/*
$pret = new stdClass();
$pret->status = 1;
$pret->msg = "Please check and try again.";
*/

$droot = "../inst/pdf";

# $pdfviewer_file is defined in extsvc.php
# slow viewer (use when not in result)
#$pdfviewer_file = "../inst/pv/web/viewer.html"; # uses file=$path
# fast viewer (use during result)
#$pdfviewer_file = "../inst/pv/web/viewpdf.html"; # it uses hard-coded /app/stud/pdl.php, file=$path is ignored
#$pdfviewer = "${pdfviewer_file}?t=" . filemtime($pdfviewer_file) . "&file=";


if (!isset($pdfviewer_file)) $pdfviewer_file = '../inst/pjs-latest/web/viewer.html';
$pdfviewer = "${pdfviewer_file}?file=";
#$pdfroot = dirname($_SERVER['PHP_SELF']);

$domain = "@educationboard.gov.bd";
# hidden variables, but has to be set (to ensure we are sending by our application)
if (!isset($_REQUEST['eiin']) && !isset($_REQUEST['ccode']) && !isset($_REQUEST['dcode'])) {
  $pret = result(1, "Invalid Request");
  goto the_end_pdf;
}

$board = $requested_board;
$exam = $requested_exam;
$year = $requested_year;

$headerfile = false;
$resultfile = false;
$pdffile = false;
$titlefile = "${droot}/${exam}/${year}/${board}/${board}_title.txt";
$is_sum_file = false;
$is_stat_file = false;
$has_stat_file = true;
# last 5 years' comparison
$ny = 5;
$history_resultfile = array();
switch ($requested_result_type) {
case '2':
  $requested_eiin = preg_replace("/[^0-9]+/", "", $_REQUEST['eiin']);
  $eiin = $requested_eiin;
  $headerfile = "${droot}/${exam}/${year}/${board}/header/${eiin}.txt";
  $resultfile = "${droot}/${exam}/${year}/${board}/result/${eiin}.txt";
  $sum_resultfile = "${droot}/${exam}/${year}/${board}/result/sum_${eiin}.txt";
  if (file_exists($sum_resultfile)) {
    $resultfile = $sum_resultfile;
    $is_sum_file = true;
  } else {
    $resultfile = false;
  }
  $stat_resultfile = "${droot}/${exam}/${year}/${board}/result/res_stat_${eiin}.csv";
  $pdffile = "${droot}/${exam}/${year}/${board}/pdf/${board}_${exam}_${year}_${eiin}.pdf"; # Original PDF File
  $pdfpath = "${sendfile_prefix}/${exam}/${year}/${board}/pdf/${board}_${exam}_${year}_${eiin}.pdf"; # Aliased PDF Path (not directly accessible)
  $error_notfound = "No Result found for Board/Exam/Year/EIIN: ${board}/${exam}/${year}/${eiin}. Please check whether you entered EIIN correctly. If your institution does not have EIIN you can try providing Institution Code.";
  break;
case '4':
  $requested_ccode = preg_replace("/[^0-9]+/", "", $_REQUEST['ccode']);
  $ccode = $requested_ccode;
  $resultfile = "${droot}/${exam}/${year}/${board}/result/cent_${ccode}.txt";
  $sum_resultfile = "${droot}/${exam}/${year}/${board}/result/cent_sum_${ccode}.txt";
  if (file_exists($sum_resultfile)) {
    $resultfile = $sum_resultfile;
    $is_sum_file = true;
  }
  $pdffile = "${droot}/${exam}/${year}/${board}/pdf/${board}_${exam}_${year}_cent_${ccode}.pdf"; # Original PDF File
  $pdfpath = "${sendfile_prefix}/${exam}/${year}/${board}/pdf/${board}_${exam}_${year}_cent_${ccode}.pdf"; # Aliased PDF Path (not directly accessible)
  $error_notfound = "No Result found for Board/Exam/Year/Centre Code: ${board}/${exam}/${year}/${ccode}.";
  break;
case '5':
  $requested_dcode = preg_replace("/[^0-9]+/", "", $_REQUEST['dcode']);
  $dcode = $requested_dcode;
  $resultfile = "${droot}/${exam}/${year}/${board}/result/dist_${dcode}.txt";
  $sum_resultfile = "${droot}/${exam}/${year}/${board}/result/dist_sum_${dcode}.txt";
  if (file_exists($sum_resultfile)) {
    $resultfile = $sum_resultfile;
    $is_sum_file = true;
  }
  $pdffile = "${droot}/${exam}/${year}/${board}/pdf/${board}_${exam}_${year}_dist_${dcode}.pdf"; # Original PDF File
  $pdfpath = "${sendfile_prefix}/${exam}/${year}/${board}/pdf/${board}_${exam}_${year}_dist_${dcode}.pdf"; # Aliased PDF Path (not directly accessible)
  $error_notfound = "No Result found for Board/Exam/Year/District Code: ${board}/${exam}/${year}/${dcode}.";
  break;
case '6':
  # institution statistics Info
  $requested_eiin = preg_replace("/[^0-9]+/", "", $_REQUEST['eiin']);
  $eiin = $requested_eiin;
  $headerfile = "${droot}/${exam}/${year}/${board}/header/${eiin}.txt";
  $resultfile = "${droot}/${exam}/${year}/${board}/result/res_stat_${eiin}.csv";
  $is_stat_file = true;
  $error_notfound = "No Result Analytics found for Board/Exam/Year/EIIN: ${board}/${exam}/${year}/${eiin}.";

  for ($i = 0; $i < $ny; $i++) {
    $yr = $year - $i;
    $hstat = "${droot}/${exam}/${yr}/${board}/result/res_stat_${eiin}.csv";
    if (file_exists($hstat)) {
      $history_resultfile[$yr] = $hstat;
    }
  }
  break;
case '7':
  # this is set for tree view

  $_SESSION['btree'] = 'yes';
  $_SESSION['year'] = $year;
  $_SESSION['board'] = $board;
  $_SESSION['exam'] = $exam;

  # board statistics Info
  $resultfile = "${droot}/${exam}/${year}/${board}/res_stat.csv";
  $is_stat_file = true;
  $error_notfound = "No Result Analytics found for Board/Exam/Year: ${board}/${exam}/${year}.";
  for ($i = 0; $i < $ny; $i++) {
    $yr = $year - $i;
    $hstat = "${droot}/${exam}/${yr}/${board}/res_stat.csv";
    if (file_exists($hstat)) {
      $history_resultfile[$yr] = $hstat;
    }
  }
  break;
default:
  $pret = result(1, "Invalid Request");
  goto the_end_pdf;
  break;
}

#$requested_eiin = preg_replace("/[^0-9]+/", "", $_REQUEST['eiin']);

$authtype = "esif"; # authenticate through eSIF password

/*
if (!checkauth($eiin, $pass, $board, $exam, $authtype)) {
  die("<script type=\"text/javascript\">alert('Authentication Failure! Please contact board for password.'); history.go(-1);</script>");
}
 */

$pdfget = "/c/pdl.php";
$btree = "/app/stud/btree_c.html";

#session_start();

$content = "";
if ($titlefile && file_exists($titlefile)) $content .= "<center>" . nl2br(file_get_contents($titlefile)) . "</center><br/>";
if ($headerfile && file_exists($headerfile)) $content .= nl2br(file_get_contents($headerfile)) . "<br/>";
#if (file_exists($resultfile)) $content .= file_get_contents($resultfile) . "<br/>";
if ($resultfile && file_exists($resultfile)) {

  $res = trim(file_get_contents($resultfile));

  $charts = array(); # default is 'Pie (3d)'
  $charts_alt = array();
  $tot_app = $tot_pass = $tot_fail = $tot_gpa5 = $tot_gpa4 = $tot_gpa3 = $tot_gpa2 = $tot_gpa1 = 0;
  $tot_app_male = $tot_app_female = $tot_pass_male = $tot_pass_female = $tot_gpa5_male = $tot_gpa5_female = 0;
  if ($is_stat_file) {

    $have_alt = true; # have alternate view (data view) available
    #if ($_SERVER['REMOTE_ADDR'] == '27.147.204.49') {
    #$have_alt = true;
    #}

    $notice = "<i><u>Note:</u> In Chart the fractional part in percentage calculation is rounded up to one digit after decimal point. So, 67.23 may become 67.2 and 39.68 may become 39.7. Percentage of GPA 5 is considered among passed students.<br/><u>Disclaimer:</u> This is Unofficial Analytics Report Automatically generated by computer program directly from raw result data provided by Education Board during result publication.<br/>.: Courtesy of Nixtec Systems :.</i>";
    $content .= "<center><h4><u>RESULT ANALYTICS</u></h4></center>";
    $content .= "<div class=\"alert alert-info text-center\" id=\"err_msg\">$notice</div>";
    $cinfos = csv2obj($res); # delimiter is ','
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
    $notice = "<b><u>Statement:</u></b> Total $tot_app ($tot_app_male male and $tot_app_female female) students appeared in exam. Among them $tot_pass ($tot_pass_male male and $tot_pass_female female) students passed, i.e. secured minimum GP 1.0 in every compulsory and elective subject. Percentage of Pass is $percent_pass. Total $tot_gpa5 ($tot_gpa5_male male and $tot_gpa5_female female) students secured GPA 5.00.";


    $content .= "<div class=\"alert alert-success text-center\" id=\"stmt\">$notice</div>";


    # pass vs fail (not passed)
    $obj = new stdClass();
    $obj->id = "pass_fail_pie";
    $obj->type = "pie";
    $obj->attr = "3d";
    $obj->title = "Passed vs Not Passed [Year: $year] (Total Appeared: $tot_app)";
    $obj->data[] = ["Type", "Count"];
    $obj->data[] = ["Passed ($tot_pass)", $tot_pass];
    $obj->data[] = ["Not Passed ($tot_fail)", $tot_fail]; # multiple records (rows) may be there
    $charts[] = $obj;
    if ($have_alt) $charts_alt[] = $obj; # no alternative view

    # gpa pie
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
    #$obj->data[] = ["Not Passed ($tot_fail)", $tot_fail];
    $charts[] = $obj;
    $charts_alt[] = $obj; # no alternative view

    if (count($history_resultfile) > 1) {
      $yrs = array();
      $gnd = array();
      # history_resultfile is already file_exists tested
      foreach ($history_resultfile as $yr => $resultfile) {
	$yrs[] = $yr;
	$res = trim(file_get_contents($resultfile));

	$cinfos = csv2obj($res); # delimiter is ','
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

      #$history_chart_type = "bar";

      # pass vs fail
      $obj = new stdClass();
      $obj->id = "pass_fail_history";
      $obj->type = "bar"; # default chart type
      $obj->attr = "stacked";
      $obj->title = "[History] Passed vs Not Passed (Among Appeared)";
      //$obj->data[] = ["Year", "Passed", "Not Passed"];

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
	  # additional info for alternative view
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
      if ($have_alt) $charts_alt[] = $alt_obj;


      # gpa countdown
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
    }
  } else {
    $_SESSION['sapi'] = $sapi; # we store this so that we can determine which Sendfile method we should use
    $_SESSION['pdf_path'] = $pdfpath;
    $res = str_replace(">\n", "<br/>", $res);
    if ($is_sum_file) {
      $res = preg_replace("/>([A-Za-z0-9\.& -]+)\n/", "<br/><font color=red>\\1</font>: ", $res); # Group
      $res = preg_replace("/=([0-9]+)\n/", "=\\1; ", $res); # Count
      $content .= "<center><h4><u>RESULT SUMMARY</u></h4></center>";
      if ($has_stat_file) {
	$notice = "*** <i>For Result Analytics, please search again with '<u>Analytics</u>' Result Type</i> ***";
	$content .= "<div class=\"alert alert-info text-center\" id=\"err_msg\">$notice</div>";
      }
    } else {
      $res = preg_replace("/>([A-Za-z0-9\.& -]+)\n/", "<br/><center><font color=red>\\1</font></center>", $res); # Group
      $res = preg_replace("/=([0-9]+)\n/", "=\\1<br/>", $res); # Count
    }
    $res = preg_replace("/\+([^\n]+)\n/", "<br/><br/><font color=blue>\\1</font>", $res);

    $content .= $res;
  }

} else {
  $pret = result(1, $error_notfound);
  goto the_end_pdf;
  #$content .= "<center><font color=red>No Result found for EIIN: $eiin</font></center>\n";
}

# Display Result Here
$extra = new stdClass();
$extra->content = $content;
if ($is_stat_file) {
  $extra->charts = $charts;
  $extra->have_alt = 0;
  if ($have_alt) {
    $extra->have_alt = 1;
    $extra->charts_alt = $charts_alt;
    //$extra->charts = $charts_alt; # currently using Tabular view for historical data
  }
}

if (isset($_SESSION['btree']) && $_SESSION['btree'] == true) {
  $extra->tree = $btree;
}

if ($pdffile && file_exists($pdffile)) {
  $extra->pdfname = basename($pdffile);
  $extra->download = $pdfget;
  #$extra->view = "${pdfviewer}$pdfget&t=" . time();
  $extra->view = "${pdfviewer}$pdfget";
}

$pret = result(0, "Success", $extra);

the_end_pdf:

#die();

?>
