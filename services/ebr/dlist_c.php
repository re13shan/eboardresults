<?php

# district listing (show all districts in a board)

#header("Content-type: application/json");

$dists = array();
if (isset($_REQUEST['board']) && isset($_REQUEST['exam']) && isset($_REQUEST['year'])) {
  $requested_board = preg_replace("/[^a-z]+/", "", $_REQUEST['board']);
  $requested_exam = strtolower(preg_replace("/[^a-zA-Z]+/", "", $_REQUEST['exam']));
  $requested_year = preg_replace("/[^0-9]+/", "", $_REQUEST['year']);

  if ($requested_exam == "dibs") {
    $requested_exam = "hsc";
    $requested_board = "dibs";
  }

  $nr = 0;
  $dist_file = "../inst/pdf/${requested_exam}/${requested_year}/${requested_board}/dist.csv";
  #file_put_contents("/tmp/zzz.txt", $dist_file . "\n", FILE_APPEND);
  if (file_exists($dist_file)) {
    $lines = explode("\n", trim(file_get_contents($dist_file)));
    $sorted = array();
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

    $__res = json_encode($dists);
    $server->cache[$CACHE_ID]->set($CACHE_KEY, [ $CACHE_KEY1 => $__res, '__ts' => time() ]);
    return;
  }
}

the_end_dlist:
$__res = json_encode($dists);

?>
