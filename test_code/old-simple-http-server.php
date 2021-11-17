<?php

# SWOOLE_BASE: reactor based mode, the business logic is running in the reactor
# SWOOLE_PROCESS: multiple process mode, the business logic is running in child processes, the default running mode of server
# SWOOLE_BASE can be used for asynchronous operations (need to test properly)
# There must be no Locking
# Benchmark shows SWOOLE_BASE has better performance in Hello World benchmark (where there is No Blocking)

# Protocol specs: unix, http, https, tcp, udp, ws (websocket), mqtt

/*
$svc_listen = array(
  'nixtec_http' => array('http://unix:/vh/p/sock.d/http-dev.sock', 'http://127.0.0.1:9501', 'http://[::1]:9501', 'https://127.0.0.1:95443', 'https://[::1]:95443'),
#  'nixtec_tcp' => array('tcp://unix:/vh/p/sock.d/tcp-dev.sock', 'tcp://127.0.0.1:9601', 'tcp://[::1]:9601', 'tcps://127.0.0.1:96443', 'tcps://[::1]:96443'),
#  'nixtec_udp' => array('udp://unix:/vh/p/sock.d/tcp-dev.sock', 'udp://127.0.0.1:9503', 'udp://[::1]:9503'),
##  'nixtec_ws' => array('ws://unix:/vh/p/sock.d/ws-dev.sock', 'ws://127.0.0.1:9504', 'ws://[::1]:9504'),
#  'nixtec_mqtt' => array('mqtt://unix:/vh/p/sock.d/mqtt-dev.sock', 'mqtt://127.0.0.1:9505', 'mqtt://[::1]:9505'),
);
*/


$svc_map = array(
  'http' => 'http',
  'https' => 'http',
  'tcp' => 'tcp',
  'tcps' => 'tcp',
  'udp' => 'udp',
  'udps' => 'udps',
  'mqtt' => 'mqtt',
  'ws' => 'ws',
);
$svc_opts = array(
  'http' => array(
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
	),
  'tcp' => array(),
  'udp' => array(),
  'mqtt' => array('open_mqtt_protocol' => true),
);

$svc_listen = array(
  #'nixtec_http_local' => array('http://unix:/vh/p/sock.d/http-dev.sock', 'http://127.0.0.1:9501', 'http://[::1]:9501', 'https://127.0.0.1:9502', 'https://[::1]:9502'),
  'nixtec_http' => array('http://unix:/vh/p/sock.d/http-dev.sock', 'http://0.0.0.0:9501', 'http://[::1]:9501', 'https://0.0.0.0:9502', 'https://[::1]:9502'),
  #'nixtec_tcp' => array('tcp://unix:/vh/p/sock.d/tcp-dev.sock', 'tcp://127.0.0.1:9601', 'tcp://[::1]:9601'),
  #'nixtec_udp' => array('udp://unix:/vh/p/sock.d/udp-dev.sock', 'udp://127.0.0.1:9701', 'udp://[::1]:9701'),
  #'nixtec_mqtt' => array('mqtt://unix:/vh/p/sock.d/mqtt-dev.sock', 'mqtt://127.0.0.1:9801', 'mqtt://[::1]:9801'),
);
$svc_mode = SWOOLE_BASE;


#$tls_key_file = '/etc/letsencrypt/live/nixtecsys.com/privkey.pem';
#$tls_cert_file = '/etc/letsencrypt/live/nixtecsys.com/fullchain.pem';
#tls_CAfile = '/etc/letsencrypt/live/nixtecsys.com/fullchain.pem';

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


$server->on('workerstart', function ($server, $id) {
  echo "Worker started: id=${id}.\n";
});
$server->on('workerstop', function ($server, $id) {
  echo "Worker stopped: id=${id}.\n";
});

$fnlist = array();
require('services/core/core.php');
require('services/ebr/ebr.php');

// http && http2
$server->on('request', function (Swoole\Http\Request $request, Swoole\Http\Response $response) use ($fnlist) {
  $__uri = $request->server['request_uri'];
  $uparts = explode('/', $__uri);
  if (count($uparts) > 2) {
    $ns = $uparts[1];
    $fn = $uparts[2];
    if (isset($fnlist[$ns]) && isset($fnlist[$ns][$fn])) {
      list($code, $data) = $fnlist[$ns][$fn]();
      if ($code != 0) {
        $response->status($code);
	$response->end($data);
      }
    } else {
      $response->status(404);
      $response->end('Resource Not Found');
    }
  } else {
    #echo "http and http2\n";
    $response->status(404);
    $response->end('Resource Not Found');
  }
});

$server->on('open', function (Swoole\WebSocket\Server $server, Swoole\Http\Request $request) {
  echo "websocket connection open: {$request->fd}\n";
  $server->tick(1000, function() use ($server, $request) {
    $server->push($request->fd, json_encode(['hello', time()]));
  });
});
// websocket
$server->on('message', function (Swoole\WebSocket\Server $server, Swoole\WebSocket\Frame $frame) {
  echo "message on websocket: {$frame->fd}\n";
  $server->push($frame->fd, 'Hello ' . $frame->data);
});

echo "Starting Server...\n";
$server->start();

die();

$srvdata = new stdClass();

$mp = new Swoole\Database\MysqliPool((new Swoole\Database\MysqliConfig)
          ->withHost('127.0.0.1')
	  ->withPort(3314)
	  ->withUnixSocket('/var/run/mysqld/mysqld-nixtec.sock')
	  ->withDbname('nixtec_api_dev')
	  ->withUsername('nixtec_api_dev')
	  ->withPassword('nixtec_api_dev_password_here'));
$srvdata->mp = $mp;
#$statement = $mysql->prepare("SELECT * FROM `ssc_dhaka_2020` WHERE roll_no='123456' LIMIT 1");
$srvdata->unixsock = $unixsock; # so that we can access it from within callbacks

$http->srvdata = &$srvdata; # this will ensure we get access the data from callbacks

/* onStart event with 'SWOOLE_BASE' is not recommended, because there will be manager process. Do everything in the manager process */
/*
$http->on('start', function ($server) {
  echo "Server has started.\n";
  if (!chmod($server->srvdata->unixsock, 0666)) {
    echo "*** Failed to chmod " . $server->srvdata->unixsock . "\n";
  }
});
*/
/*
$http->on('shutdown', function ($server) {
  echo "Server is shutting down.\n";
});
*/

$http->on('workerstart', function ($server, $id) {
  echo "Worker started: id=${id}.\n";
});
$http->on('workerstop', function ($server, $id) {
  echo "Worker stopped: id=${id}.\n";
});

/*
$http->on('timer', function ($server, $interval) {
  echo "Time triggered: interval=${interval}.\n";
});
*/

/* connect event is meaningful in SWOOLE_BASE only */
/*
$http->on('connect', function ($server, $fd) {
  echo "New Connection Established: fd=${fd}.\n";
});
*/

/*
$http->on('receive', function ($server, $fd, $reactor_id, $data) {
  echo "Received Data: fd=${fd}, reactor_id=${reactor_id}, raw_data=|$data|.\n";
});
*/

/*
# 'packet' is for UDP server
$http->on('packet', function ($server, $data, $client_info) {
  echo "Received Data: raw_data=|$data|, client_info=" . print_r($client_info) . "\n";
});
*/
/* close event is meaningful in SWOOLE_BASE only */
/*
$http->on('close', function ($server, $fd, $reactor_id) {
  echo "Closing Connection: fd=${fd}, reactor_id=${reactor_id}.\n";
});
*/
/*
$http->on('task', function ($server, $task_id, $from_worker_id, $data) {
  echo "Task Received: task_id=${task_id}, from_worker_id=${from_worker_id}, raw_data=|${data}|.\n";
});
$http->on('finish', function ($server, $task_id, $data) {
  echo "Task Finished: task_id=${task_id}, result_data=|${data}|.\n";
});
$http->on('pipemessage', function ($server, $from_worker_id, $message) {
  echo "Received Message: from_worker_id=${task_id}, message=|${data}|.\n";
});
$http->on('workererror', function ($server, $worker_id, $worker_pid, $exit_code, $signal) {
  echo "Worker Stopped from Error: worker_id=${worker_id}, worker_pid=$worker_pid, exit_code=$exit_code, signal=$signal.\n";
});
$http->on('managerstart', function ($server) {
  echo "Manager Process Started.\n";

});
$http->on('managerstop', function ($server) {
  echo "Manager Process Stopped.\n";
});
*/

# Real-world code follows
$http->on('request', function (Swoole\Http\Request $request, Swoole\Http\Response $response) use ($srvdata) {
/*
  $mysql = $srvdata->mp->get();
  $roll_no = '12345';
  $mysql->query("SELECT * FROM `ssc_dhaka_2020` WHERE roll_no='${roll_no}' LIMIT 1");
  assert(count($result) > 0);
  $srvdata->mp->put($mysql); # not closing, just putting back to pool, so the statements will stay
  */

  $response->end('Hello World! ' . rand(100000, 999999));
});
echo "PID: " . posix_getpid() . "\n";
echo "Starting Server ...\n";

$http->start();

?>
