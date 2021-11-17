<?php

use Swoole\Coroutine\Http\Client;


$counter = new Swoole\Atomic(0);


$workerNum = 10;
$pool = new Swoole\Process\Pool($workerNum);

$pool->on("WorkerStart", function ($pool, $workerId) use ($counter) {
  echo "Worker#{$workerId} is started\n";

  while (true) {
    Co\run(function() use ($counter) {
      #$n = 4096;
      $in = 100;
      $jn = 1000;
      for ($i = 0; $i < $in; $i++) {
	go(function() use ($jn, $counter) {

/*
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
	  #$url = 'http://eboardresults.com/c/dot.php'; # Swoole Service, 0.570 s for 4096 requests
	  $url = 'http://127.0.0.1:9501/c/dot.php'; # Swoole Service, 0.570 s for 4096 requests
	  #$ch = curl_init();
	  curl_setopt($ch, CURLOPT_URL, $url);
	  $output = curl_exec($ch);
	  $counter->add(1);
	  if ($output === FALSE) {
	    $resp = "CURL Error:". curl_error($ch). " ". $url. "\n";
	    echo $resp;
	    return $resp;
	  }
	  #echo $output;
	  #curl_close($ch);
	}
	curl_close($ch);
*/

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
	    $counter->add(1);
	    #$url = 'http://eboardresults.com/dot.html'; # Nginx Static: 0.578 s for 4096 requests
	    #$url = 'http://eboardresults.com/dot.php'; # traditional php-fpm, throws error for many connections
	    #$url = 'http://eboardresults.com/c/dot.php'; # Swoole Service, 0.570 s for 4096 requests
	    #echo $cli->body;
	    #curl_close($ch);
	  }
	  $cli->close();


	});
      }
    });

    #echo "Created " . $in . " coroutines, each running $jn requests.\n";
    echo "Counter = " . $counter->get() . "\n";
  }

});

$pool->on("WorkerStop", function ($pool, $workerId) {
  echo "Worker#{$workerId} is stopped.\n";
});

$pool->start();

?>
