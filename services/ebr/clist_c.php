<?php

# centre listing, shows all centres in a district

#header("Content-type: application/json");

$cents = array();
if (isset($_REQUEST['board']) && isset($_REQUEST['exam']) && isset($_REQUEST['year']) && isset($_REQUEST['dcode'])) {
  $requested_board = preg_replace("/[^a-z]+/", "", $_REQUEST['board']);
  $requested_exam = strtolower(preg_replace("/[^a-zA-Z]+/", "", $_REQUEST['exam']));
  $requested_year = preg_replace("/[^0-9]+/", "", $_REQUEST['year']);
  $requested_dcode = preg_replace("/[^0-9]+/", "", $_REQUEST['dcode']);

  if ($requested_exam == "dibs") {
    $requested_exam = "hsc";
    $requested_board = "dibs";
  }

  $nr = 0;
  $cent_file = "../inst/pdf/${requested_exam}/${requested_year}/${requested_board}/dist_cent_map.csv";
  if (file_exists($cent_file)) {
    $lines = explode("\n", trim(file_get_contents($cent_file)));
    $sorted = array();
    foreach ($lines as $line) {
      $nr++;
      if ($nr == 1) continue;
      $parts = explode("|", $line);
      $dcode = $parts[0];
      if ($requested_dcode != $dcode) continue;

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
    $__res = json_encode($cents);
    $server->cache[$CACHE_ID]->set($CACHE_KEY, [ $CACHE_KEY1 => $__res, '__ts' => time() ]);
    return;
  }
}

the_end_clist:
$__res = json_encode($cents);

?>
