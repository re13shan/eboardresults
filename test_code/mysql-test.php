<?php

$s = microtime(true);
Co\run(function() {
    for ($c = 100; $c--;) {
        go(function () {
            $mysql = new Swoole\Coroutine\MySQL;
	    defer (function() use ($mysql) { $mysql->close(); });
            $mysql->connect([
                'host' => '127.0.0.1',
		'port' => 3314,
                'user' => 'nixtec_api_dev',
                'password' => '9K5vaDUjByPRgTKh',
                'database' => 'nixtec_api_dev'
            ]);
            #$statement = $mysql->prepare("SELECT * FROM `ssc_dhaka_2020` WHERE roll_no='123456' LIMIT 1");
            for ($n = 100; $n--;) {
                $result = $mysql->query("SELECT * FROM `ssc_dhaka_2020` WHERE roll_no='123456' LIMIT 1");
                #$result = $statement->execute();
                assert(count($result) > 0);
            }
        });
    }
});
echo 'use ' . (microtime(true) - $s) . ' s';

?>
