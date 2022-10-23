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

//Push a set of events and their associated data into the task queue.
$redis->lPush($taskQ,json_encode(['e'=>'print','d'=>'This is a message']));
$redis->lPush($taskQ,json_encode(['e'=>'dothis','d'=>'This is a Class message']));
$redis->lPush($taskQ,json_encode(['e'=>'dothat','d'=>'This is another Class message']));
$redis->lPush($taskQ,json_encode(['e'=>'fail','d'=>['err'=>'blearg']]));
$redis->lPush($taskQ, json_encode(['e'=>'quit', 'd'=>null]));
