<?php

// enable coroutine support for PHP CURL
Swoole\Runtime::enableCoroutine(SWOOLE_HOOK_CURL);

Co\run(function() {
  $n = 1000;
  for ($i = 0; $i < $n; $i++) {
    go(function() {
      $url = 'http://eboardresults.com/dot.html';
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
      curl_setopt($ch, CURLOPT_TIMEOUT, 10);
      $headers = array();
      $headers[] = 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.163 Safari/537.36';
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      $output = curl_exec($ch);
      if ($output === FALSE) {
        $resp = "CURL Error:". curl_error($ch). " ". $url. "\n";
	echo $resp;
        return $resp;
      }
      #echo $output;
      curl_close($ch);
    });
  }

  echo "Created $n coroutines\n";
});

?>
