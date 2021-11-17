<?php


$fnlist['mysqlpool'] = [

  'init' => function ($args=null) use ($fnlist) {
    # args: app, config
    $app = $args['app'] ?? 'noapp';
    $id = "mysqlpool";
    $type = 'mysql'; # or even mysqli (same)
    $default_config = [ 'title' => 'MySQL Pool', 'host' => 'localhost', 'port' => 3306, 'sock' => '/var/run/mysqld/mysqld.sock', 'dbname' => 'information_schema', 'charset' => 'utf8mb4', 'username' => 'demo', 'password' => 'demo' ];
    $config = $args['config'] ?? $default_config;


    $cfg = (new Swoole\Database\MysqliConfig())
    	->withHost($config['host'] ?? $default_config['host'])
	->withPort($config['port'] ?? $default_config['port'])
	#->withUnixSocket($config['sock'] ?? $default_config['sock'])
	->withCharset($config['charset'] ?? $default_config['charset'])
	->withDbname($config['dbname'] ?? $default_config['dbname'])
	->withUsername($config['username'] ?? $default_config['username'])
	->withPassword($config['password'] ?? $default_config['password']);


    $sconfig = [ 'app' => $app, 'id' => $id, 'title' => $config['title'] ?? 'MySQL Pool', 'type' => $type, 'cfg' => $cfg ];

    #print_r($sconfig);

    echo "Configuring MySQL Pool [{$config['title']}]\n";
    return $fnlist['pool']['addcfg']($sconfig);
  }, 

];

?>
