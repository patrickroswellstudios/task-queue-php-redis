<?php
include_once dirname(__FILE__) . "/vendor/autoload.php";
$redis = new Predis\Client([
		'scheme' => 'tcp',
		'host' => '127.0.0.1',
		'port' => 6379,
		'database' => 0,
		'read_write_timeout' => 0
		]);

function worker($redis, $taskQ, $di, $id=null) {
  if (null === $id) {
    $id = getmypid();//ideally, for restarting task queues, this would be passed in from PM2 or Supervisor or whatever.
  }
  $workerID = $taskQ . ':worker:' . $id;
  //Announce that I'm a worker
	$redis->lPush($taskQ . ':workers', $workerID);
	$run = true;
	while ($run) {
		//check my queue for anything directed at me personally
		$message = $redis->lGet($workerID, 0);
		if (!$message) {
			//check the worker pool queue
			$message = $redis->rPopLPush($taskQ, $workerID);
		}
    //the non-blocking lookups can return nothing if there is nothing
		if ($message) {
			try {
        //serialization of the content is a big issue. This is just JSON.
				$o = json_decode($message);
				$d = $o->d;
				//var_export($o);

				//work happens here
				if ($o->e) {
					if (method_exists($di, $o->e)) {
						call_user_func([$di, $o->e], $d);
					} else {
						switch ($o->e) {
							case 'print':
								echo $d, "\n";
								break;
							case 'fail':
								throw new Exception($d->err);
								break;
							case 'quit':
								$run = false;
								break;
							default:
								throw new Exception('message had an unrecognized event');
								break;
						}
					}
				} else {
					throw new Exception('message did not contain an event');
				}
				//success:
				//log it
				echo $workerID, ':', time(), ' OK:    ',$o->e,"\n";
			} catch (Exception $e) {
				//fail:
				echo $workerID, ':', time(), ' ERROR: ', $e->getMessage(), "\n";
				//iff this error is just something with this particular worker, run it again:
				//$redis->rPush($taskQ, $message);
				//or
				//$redis->rPush($taskQ . ':failed', $message);
				//and then later clean out the failed queue with something else.
			} finally {
				//always:
				$redis->lRem($workerID, 1, $message);
			}
		} else {
      //There was nothing to do, so do nothing
			sleep(5);
      //This is also big issue: ideally, adding things to the data store could wake up the worker. This is easy to do if both are inside the same app, less easy with a datastore that might be running on another machine.
      //without a solution to that, just wait 5 seconds for more data.
      //idea: exponential decay. Assume one worker is handling all the tasks 99% of the time, but once an hour, an additional worker would wake up, run for as long as needed, then sleep for longer and longer until needed again.
		}
	}
  //The infinite loop has ended
  //Clean up the list of workers
	$redis->lRem($taskQ . ':workers', 0, $workerID);
  //if I was less lazy, it would also do this on ^C
}


//Here's a class for all your stuff
class ExampleWorker {
	public function dothis($d) {
    var_export($d);
    echo "\nI'm doing this!\n";
	}

	public function dothat($d) {
    var_dump($d);
    echo "\nI'm doing that!\n";
	}
}
//If you don't want to get into a class, see the switch in the worker function.


//Start it up
worker($redis, 'example', new ExampleWorker());
