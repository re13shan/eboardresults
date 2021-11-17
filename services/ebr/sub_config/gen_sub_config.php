<?php

#header("Content-type: text/plain");


$ext = ".json";
foreach (glob("*_sub_*${ext}") as $file) {
  $file = str_replace("${ext}", "", $file);
  $parts = explode("_", $file);
  $exam = $parts[0];
  $board = $parts[2];
  $year = $parts[3];

  $sub_year[$exam][$board][] = $year;
}

echo "<?php\n";

echo "\$sub_config['sub_year'] = ";
var_export($sub_year);
echo ";\n?>\n";

?>
