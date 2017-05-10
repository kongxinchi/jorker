<?php
require_once(dirname(__DIR__) . '/vendor/autoload.php');

/**
 * You can stop process use CTRL+C,
 * next time you run this script, the arg $start will be the first not execute job
 */

$manager = new \Jorker\JobForkerManager(1);
$manager->allot(function($start) {
    $i = is_null($start) ? 0 : $start['i'];
    for(; $i < 100; $i++) {
        usleep(100000);
        yield ['i' => $i];
    }
})->run(function($job, \Jorker\Slave\Slave $slave) {
    // DO SOMETHING...
    $slave->logger()->info("use this way if you want print log {$job['i']}.");
});