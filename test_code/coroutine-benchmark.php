<?php

use Swoole\Coroutine\Http\Client;


Co\run(function() {
  #$n = 4096;
  $in = 1000;
  $jn = 1000;
  for ($i = 0; $i < $in; $i++) {
    go(function() use ($jn) {
      #$cli = new Client('127.0.0.1', 80);
      $cli = new Client('127.0.0.1', 9501);
      $cli->setHeaders([
        'Host' => 'eboardresults.com',
	'User-Agent' => 'Chrome/49.0.2587.3',
	'Accept' => 'text/html,text/plain',
	'Accept-Encoding' => 'gzip',
      ]);
      $cli->set(['timeout' => 1]);
      for ($j = 0; $j < $jn; $j++) {
        $cli->get('/c/dot.php');
	#$url = 'http://eboardresults.com/dot.html'; # Nginx Static: 0.578 s for 4096 requests
	#$url = 'http://eboardresults.com/dot.php'; # traditional php-fpm, throws error for many connections
	#$url = 'http://eboardresults.com/c/dot.php'; # Swoole Service, 0.570 s for 4096 requests
	#echo $cli->body;
	#curl_close($ch);
      }
      $cli->close();
    });
  }

  echo "Created " . $in . " coroutines, each running $jn requests.\n";
});

?>
