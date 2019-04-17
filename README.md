# Very Simple Task Queue in PHP, using Redis

See all the guts of a working task queue executed in PHP using Redis commands. It is designed as an educational example, not a library for your shipping product. Although the simplicity should make it easy for you to incorporate it into an existing php application, if you wanted Laravel's queues but without the complexity or requirements of the rest of Laravel.

### task queue vs pub/sub

Redis has pub/sub built in. I assume you are already familiar with that. This is not pub/sub. You can add tasks without workers present. You can add more than one worker and tasks are distributed to exactly one worker. Workers can die mid-task and the task persists. Workers can add new tasks to the queue and have other workers work on it while the original continues. Workers can add a series of tasks to their own queue and ensure they are executed in order.

### event queues

I'm on a bit of a tear about remote event queues as the easy solution to remote computing, fixing the slowness of thin client/RDP/VNC without requiring already event driven UI apps to be completely rewritten, just split in half. This also works for using more simultaneous cores without threading. You'll note there is only the one channel, but multiple events are passed through it.

## install instructions

* install Redis.io
* add predis: `composer require predis/predis`

## run instructions

* open 2 terminals:
* in #1 run `php producer.php`.
* in #2 run `php consumer.php`. The consumer will quit, if the producer sends the quit event, or wait until you control-C it.
* Or: run the consumer, then the producer. (Note that if there aren't any events waiting, there's a 5 second wait for more events, so the consumer terminal won't react immediately.) Or run 4 consumers, then 4 producers! Or 4 producers, then 4 consumers, one at time.
* run `php status.php` at various points in the process, and see how tasks or workers can wait for processing.
* run `redis-cli` and mess with the raw data. `del 'example:workers'` will clean out the list of workers, should workers die unexpectedly. Or `push 'example' '{"e":"quit"}'` to quit the worker.

With multiple workers, you might see that the workers don't execute the tasks in the order you might expect. That's the "fun" part of asynchronous execution.

### other fun things to do

Add terminators to clear out stalled workers. Add wait-until times to tasks. Add cancellation, to delete a pending task or find a worker mid-task and gently kill it, or at least set a flag the code can check while it is looping.

### better projects

This exists mostly as an exercise. You might be more interested in [Bernard](https://bernard.readthedocs.io/) to do some actual work.
