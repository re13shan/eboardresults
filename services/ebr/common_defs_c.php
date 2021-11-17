<?php

function defs_c()
{

  $d = array();

  $result_type = [
    1 => "Individual Result",
    2 => "Institution Result"
  ];


  # More Functions
  $result_type[4] = "Centre Result"; // will be disabled if Enough Bandwidth (50%) is used
  $result_type[5] = "District Result"; // will be disabled if Enough Bandwidth (50%) is used
  $result_type[6] = "Institution Analytics";
  $result_type[7] = "Board Analytics";

  #if ($env && isset($env['NIXTEC_VERIFIED'])) {
  #  $result_type[3] = "Testimonial";
  #  $result_type[8] = "Tree of Institutions";
  #  $allowed_result_map[8] = "inst-tree.php"; # control transfer to the script
    # New options might be available
  #}
  $d['result_type'] = $result_type;
  $d['allowed_result_type'] = array_keys($result_type);
  $d['allowed_result_type_group'] = array(2, 4, 5, 6, 7); # result_pdf.php will be invoked

  $d['board_map'] = array(
    'barisal' => "Barisal",
    'chittagong' => "Chittagong",
    'comilla' => "Comilla",
    'dhaka' => "Dhaka",
    'dinajpur' => "Dinajpur",
    'jessore' => "Jessore",
    'madrasah' => "Madrasah",
    'rajshahi' => "Rajshahi",
    'sylhet' => "Sylhet",
    'mymensingh' => "Mymensingh",
    'tec' => "Technical",
  #  'bou' => "Open University",
  );

  $d['board_website_map'] = array(
    'dhaka' => "http://dhakaeducationboard.gov.bd/",
    'rajshahi' => "http://www.rajshahieducationboard.gov.bd/",
    'madrasah' => "http://www.ebmeb.gov.bd/",
    'dibs' => "http://dhakaeducationboard.gov.bd/",
    'barisal' => "http://www.barisalboard.gov.bd/",
    'chittagong' => "http://www.bise-ctg.gov.bd/",
    'comilla' => "http://www.comillaboard.gov.bd/",
    'dinajpur' => "http://www.dinajpureducationboard.gov.bd/",
    'jessore' => "http://www.jessoreboard.gov.bd/",
    'sylhet' => "http://sylhetboard.gov.bd/",
    'tec' => "http://www.bteb.gov.bd/",
    'bou' => "https://www.bou.edu.bd/",
    'mymensingh' => "http://www.mymensingheducationboard.gov.bd/",
  );

  /*
  # we consider 'dibs' as board, hsc_dibs_2017, for example
  define('CHECK_ENLISTED', true);
  $board_enlisted = array(
    'dhaka', 'dibs', 'rajshahi', 'madrasah'
  );
   */

  #$board_enlisted = array_keys($board_website_map);


  /*
  $year_map = array(
    '2016' => "2016",

  );
  */
  $cur_year = date("Y");
  $min_year = 1996;
  $year_map = array();
  for ($i = $cur_year; $i >= $min_year; $i--){
    $year_map[$i] = $i;
  }
  $d['year_map'] = $year_map;

  /*
  $marks_start_config = array(
    'ssc' => 2017,
    'hsc' => 2016,
    'jsc' => 0,
  );
  */

  # configuration for result when Grading System was introduced (to show 'Grade' or 'Marks' in Table Title
  $d['grd_start_config'] = array(
    'ssc' => 2001,
    'hsc' => 2003,
    'jsc' => 2010
  );
    

  $d['exam_map'] = array(
    'jsc' => "JSC/JDC",
    'ssc' => "SSC/Dakhil/Equivalent",
    'hsc' => "HSC/Alim/Equivalent",
    'dibs' => "DIBS (Diploma in Business Studies)",
  );

  $d['exam_translate'] = array("jdc" => "jsc", "dakhil" => "ssc", "alim" => "hsc");

  $d['exam_name_show_map'] = array(
    'JSC' => "Junior Secondary Certificate (JSC)",
    'SSC' => "Secondary School Certificate (SSC)",
    'HSC' => "Higher Secondary Certificate (HSC)"
  );

  /*
  foreach ($exam_map as $k => $v) {
    $exam_map_board[$k]['default'] = $v;
  }
  $exam_map_board['jsc']['madrasah'] = "jdc";
  $exam_map_board['ssc']['madrasah'] = "dakhil";
  $exam_map_board['hsc']['madrasah'] = "alim";
   */

  /*
  # board-wise exam name display (dakhil for madrasah, ssc for regular boards)
  function get_exam_name($board, $exam)
  {

  }
   */

  /*
  $file = fopen('ssc_center_dhaka_2016.csv', 'r');
  while (($row = fgetcsv($file,4096,'|')) !== FALSE) {
    $dist = $row[0];
    $center_code =  $row[1];
    $name = $row[2];
    $dist_center_map[$dist][$center_code] = $name;
  }
  fclose($file);

  #print_r($dist_center_map);

  $file1 = fopen('ssc_zilla_dhaka_2016.csv', 'r');
  while (($row1 = fgetcsv($file1,4096,'|')) !== FALSE) {
    $dist_code =  $row1[0];
    $dist_name = $row1[1];
    $district_map[$dist_code] = $dist_name;
  }
  fclose($file1);
  */
  #print_r($district_map);

  return $d;
}

?>
