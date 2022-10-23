<?php

include_once "../vendor/autoload.php";
$redis = new Predis\Client([
		'scheme' => 'tcp',
		'host' => '127.0.0.1',
		'port' => 6379,
		'database' => 0,
		'read_write_timeout' => 0
		]);

$taskQ = 'example';

echo 'System Status',"\n";
echo '  in queue: ', $redis->lLen('example'),"\n";
$workers = $redis->lRange($taskQ . ':workers', 0, -1);
echo '  workers: ',count($workers),"\n";
if ($workers) {
	foreach ($workers as $worker) {
		echo '    ',$worker,': ', $redis->lLen($worker),"\n";
	}
}
