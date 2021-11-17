<?php

// enable coroutine support for PHP CURL
Swoole\Runtime::enableCoroutine(SWOOLE_HOOK_CURL);

Co\run(function() {
  #$n = 4096;
  $in = 1000;
  $jn = 1000;
  for ($i = 0; $i < $in; $i++) {
    go(function() use ($jn) {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
      curl_setopt($ch, CURLOPT_TIMEOUT, 10);
      $headers = array();
      $headers[] = 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.163 Safari/537.36';
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

      for ($j = 0; $j < $jn; $j++) {
	#$url = 'http://eboardresults.com/dot.html'; # Nginx Static: 0.578 s for 4096 requests
	#$url = 'http://eboardresults.com/dot.php'; # traditional php-fpm, throws error for many connections
	$url = 'http://eboardresults.com/c/dot.php'; # Swoole Service, 0.570 s for 4096 requests
	#$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	$output = curl_exec($ch);
	if ($output === FALSE) {
	  $resp = "CURL Error:". curl_error($ch). " ". $url. "\n";
	  echo $resp;
	  return $resp;
	}
	#echo $output;
	#curl_close($ch);
      }
      curl_close($ch);
    });
  }

  echo "Created " . $in . " coroutines, each running $jn requests.\n";
});

?>
