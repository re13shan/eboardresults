<?php

# SWOOLE_BASE: reactor based mode, the business logic is running in the reactor
# SWOOLE_PROCESS: multiple process mode, the business logic is running in child processes, the default running mode of server
# SWOOLE_BASE can be used for asynchronous operations (need to test properly)
# There must be no Locking
# Benchmark shows SWOOLE_BASE has better performance in Hello World benchmark (where there is No Blocking)

# Protocol specs: unix, http, https, tcp, udp, ws (websocket), mqtt

/*
$svc_listen = [
  'nixtec_http' => ['http://unix:/vh/p/sock.d/http-dev.sock', 'http://127.0.0.1:9501', 'http://[::1]:9501', 'https://127.0.0.1:95443', 'https://[::1]:95443'],
#  'nixtec_tcp' => ['tcp://unix:/vh/p/sock.d/tcp-dev.sock', 'tcp://127.0.0.1:9601', 'tcp://[::1]:9601', 'tcps://127.0.0.1:96443', 'tcps://[::1]:96443'],
#  'nixtec_udp' => ['udp://unix:/vh/p/sock.d/tcp-dev.sock', 'udp://127.0.0.1:9503', 'udp://[::1]:9503'],
##  'nixtec_ws' => ['ws://unix:/vh/p/sock.d/ws-dev.sock', 'ws://127.0.0.1:9504', 'ws://[::1]:9504'],
#  'nixtec_mqtt' => ['mqtt://unix:/vh/p/sock.d/mqtt-dev.sock', 'mqtt://127.0.0.1:9505', 'mqtt://[::1]:9505'],
];
*/


$svc_map = [
  'http' => 'http',
  'https' => 'http',
  'tcp' => 'tcp',
  'tcps' => 'tcp',
  'udp' => 'udp',
  'udps' => 'udps',
  'mqtt' => 'mqtt',
  'ws' => 'ws',
];
$svc_opts = [
  'http' => [
  	'http_parse_post' => true,
	#'worker_num' => 4*16,
	#'worker_num' => 16,
	#'worker_num' => 1,
	#'reactor_num' => 16,
	'open_cpu_affinity' => 1,
	'enable_reuse_port' => true,
  	'open_http_protocol' => true,
	'open_http2_protocol' => true,
	'open_websocket_protocol' => true,
	'ssl_key_file' => 'privatekey.pem',
	'ssl_cert_file' => 'publickey.pem',
	],
  'tcp' => [],
  'udp' => [],
  'mqtt' => ['open_mqtt_protocol' => true],
];

$svc_listen = [
  #'nixtec_http_local' => ['http://unix:/vh/p/sock.d/http-dev.sock', 'http://127.0.0.1:9501', 'http://[::1]:9501', 'https://127.0.0.1:9502', 'https://[::1]:9502'],
  'nixtec_http' => ['http://unix:/vh/p/sock.d/eboardresults-svc.sock', 'http://0.0.0.0:9501', 'http://[::1]:9501', 'https://0.0.0.0:9502', 'https://[::1]:9502'],
  #'nixtec_tcp' => ['tcp://unix:/vh/p/sock.d/tcp-dev.sock', 'tcp://127.0.0.1:9601', 'tcp://[::1]:9601'],
  #'nixtec_udp' => ['udp://unix:/vh/p/sock.d/udp-dev.sock', 'udp://127.0.0.1:9701', 'udp://[::1]:9701'],
  #'nixtec_mqtt' => ['mqtt://unix:/vh/p/sock.d/mqtt-dev.sock', 'mqtt://127.0.0.1:9801', 'mqtt://[::1]:9801'],
];

#$svc_mode = SWOOLE_BASE;
$svc_mode = SWOOLE_PROCESS;


#$tls_key_file = '/etc/letsencrypt/live/nixtecsys.com/privkey.pem';
#$tls_cert_file = '/etc/letsencrypt/live/nixtecsys.com/fullchain.pem';
#tls_CAfile = '/etc/letsencrypt/live/nixtecsys.com/fullchain.pem';

date_default_timezone_set('Asia/Dhaka');

$server = false;

$srv = false;
foreach ($svc_listen as $k => $v) {

  if ($srv != false) break; # only one instance of server can run

  foreach ($v as $lspec) {
    $parsed = parse_url($lspec);
    $scheme = $parsed['scheme'];
    $host = $parsed['host'];
    $port = $parsed['port'] ?? 0;
    $path = $parsed['path'] ?? '';

    switch ($scheme) {
      case 'http':
      case 'https':
	$type = SWOOLE_SOCK_TCP;
	if ($path != '') {
	  $host = $path;
	  $port = 0;
	  $type = SWOOLE_UNIX_STREAM;
	  unlink($path);
	} else if (substr($host, 0, 1) == '[') { # ipv6 address
	  $host = trim($host, '[]'); # remove the []
	  $type = SWOOLE_SOCK_TCP6;
	}

	if ($scheme == 'https') $type |= SWOOLE_SSL;

	if ($srv == false) {
	  $srv = new Swoole\WebSocket\Server($host, $port, $svc_mode, $type);
	} else {
	  $srv->addListener($host, $port, $type);
	}
      break;
      case 'tcp':
	$type = SWOOLE_SOCK_TCP;
	if ($path != '') {
	  $host = $path;
	  $port = 0;
	  $type = SWOOLE_UNIX_STREAM;
	  unlink($path);
	} else if (substr($host, 0, 1) == '[') { # ipv6 address
	  $host = trim($host, '[]'); # remove the []
	  $type = SWOOLE_SOCK_TCP6;
	}

	if ($scheme == 'tcps') $type |= SWOOLE_SSL;

	if ($srv == false) {
	  $srv = new Swoole\Server($host, $port, $svc_mode, $type);
	} else {
	  $srv->addListener($host, $port, $type);
	}

      break;
	$type = SWOOLE_SOCK_UDP;
	if ($path != '') {
	  $host = $path;
	  $port = 0;
	  $type = SWOOLE_UNIX_DGRAM;
	  unlink($path);
	} else if (substr($host, 0, 1) == '[') { # ipv6 address
	  $host = trim($host, '[]'); # remove the []
	  $type = SWOOLE_SOCK_UDP6;
	}

	if ($scheme == 'udps') $type |= SWOOLE_SSL;

	if ($srv == false) {
	  $srv = new Swoole\Server($host, $port, $svc_mode, $type);
	} else {
	  $srv->addListener($host, $port, $type);
	}

      break;
      default:
      break;
    }
    echo $lspec . "\n";
  }
  $server = $srv;
  $svc_type = $svc_map[$scheme];
  $server->set($svc_opts[$svc_type]);
}

if ($server == false) die("No Server!\n");


$server->on('workerstart', function ($server, $id) use (&$fnlist) {
  echo "Worker started: id=${id}.\n";
  if ($id == 0) {
    foreach ($fnlist['ebr']['__timer_config'] as $timername => $timerspec) {
      $interval_ms = $timerspec['interval_ms'];
      $callback = $timerspec['callback'];
      echo "* Installing timer [ $timername, $interval_ms (ms), $callback (cb) ]\n";
      $server->tick($interval_ms, $callback, [ $timername, $timerspec, $fnlist ]);
    }
  }
});
$server->on('workerstop', function ($server, $id) {
  echo "Worker stopped: id=${id}.\n";
});

$fnlist = [];
$fnlist['__ds'] = [];

# load core services
require('services/core/core.php');
require('services/core/cache.php');
require('services/core/session.php');
require('services/core/pool.php');
require('services/core/mysqlpool.php');
require('services/core/captcha.php');
#require('services/core/vars.php');

# load application services
# app 'ebr' routine starts
require('services/ebr/ebr.php');
# call initialization of applications
$fnlist['ebr']['init'](); # Initialize application ebr
# app 'ebr' routine ends



// http && http2
$server->on('request', function (Swoole\Http\Request $request, Swoole\Http\Response $response) use (&$fnlist) {

  $__uri = $request->server['request_uri'];
  #echo $__uri . "\n";
  $uparts = preg_split('@/@', $__uri, -1, PREG_SPLIT_NO_EMPTY);
  #print_r($uparts);
  if (count($uparts) == 1) array_push($uparts, 'home');

  $ns = $uparts[0];
  $fn = $uparts[1];
  if (isset($fnlist[$ns]) && isset($fnlist[$ns][$fn])) {

    $accel_map = [
      'nginx' => 'X-Accel-Redirect',
      'apache' => 'X-Sendfile',
    ];
    $sapi = 'nginx';

    $env = [];
    $getargs = $request->get ?? [];
    $postargs = $request->post ?? [];
    $reqargs = array_merge($getargs, $postargs);

    $noextra = false;
    if (isset($fnlist[$ns]['__cfg']) && isset($fnlist[$ns]['__cfg']['justfunc']) && in_array($fn, $fnlist[$ns]['__cfg']['justfunc'])) {
      $noextra = true;
    }

    if ($noextra == false) {
      $sfn = '__on_req_start'; # this method will return a env array
      if (isset($fnlist[$ns][$sfn])) {
	$sargs = [ 'app' => $ns, 'fn' => $sfn, 'request' => $request, 'response' => $response, 'getargs' => $getargs, 'postargs' => $postargs, 'reqargs' => $reqargs ];
	$env = $fnlist[$ns][$sfn]($sargs);
      }
    }

    $env['server']['REMOTE_ADDR'] = $request->header['x-real-ip'] ?? null;
    $env['server']['HTTP_HOST'] = $request->header['x-forwarded-host'] ?? null;
    $env['server']['HTTPS'] = ($request->header['x-forwarded-proto'] ?? 'http') == 'https'? 'on' : 'off';
    $env['server']['TIME'] = $request->server['request_time'] ?? time();

    $args = [ 'app' => $ns, 'fn' => $fn, 'request' => $request, 'response' => $response, 'getargs' => $getargs, 'postargs' => $postargs, 'reqargs' => $reqargs, 'env' => $env ];
    list($code, $data) = $fnlist[$ns][$fn]($args);

    # just for testing purpose we need to see request data in every request
    #$env['server']['HRTIME'] = hrtime(true);
    #echo "[ " . date('r', $env['server']['TIME']) . " ] [ " . $env['server']['REMOTE_ADDR'] . " ] Request: {$__uri}" . (count($reqargs) > 0 ? "?" . http_build_query($reqargs) : "") . ", Response Code: $code, Time (ns): " . (hrtime(true) - $env['server']['HRTIME']) . "\n";
    /*
    if ($code != 200) {
      echo "\n*** DEBUG START ***\n";
      echo "Response Code=$code\n";
      echo "Response Data=$data\n";
      echo "Environment Data:\n";
      print_r($args['env']); # caller may modify the 'env' member
      echo "\n*** DEBUG END ***\n";
    }
    */

    /* not meant to serve static contents */
    $response->header("Cache-Control", "no-store");
    $response->header("Expires", "Thu, 19 Nov 1981 08:52:00 GMT"); # some really old day
    $response->header("Pragma", "no-cache");
    if (isset($args['env']['x-accel-redirect'])) {
      $response->header($accel_map[$sapi], $args['env']['x-accel-redirect']);
    }
    #$response->status($code);
    $response->status(200); # We are sending 200 always, so that the client doesn't consider the error otherwise
    $response->end($data);

    if ($noextra == false) {
      $efn = '__on_req_end';
      if (isset($fnlist[$ns][$efn])) {
	$eargs = [ 'app' => $ns, 'fn' => $efn, 'request' => $request, 'response' => $response, 'env' => $args['env'] ];
	$fnlist[$ns][$efn]($eargs);
      }
    }

  } else {
    $response->status(404);
    $response->end('Resource Not Found');
  }
});

$server->on('open', function (Swoole\WebSocket\Server $server, Swoole\Http\Request $request) use (&$fnlist) {
  echo "websocket connection open: {$request->fd}\n";
  /*
  $server->tick(1000, function() use ($server, $request) {
    $server->push($request->fd, json_encode(['hello', time()]));
  });
  */
});

// websocket
$server->on('message', function (Swoole\WebSocket\Server $server, Swoole\WebSocket\Frame $frame) use (&$fnlist) {
  echo "message on websocket: {$frame->fd}\n";
  $server->push($frame->fd, 'Hello ' . $frame->data);
});

echo "Starting Server...\n";
$server->start();

die();

?>
