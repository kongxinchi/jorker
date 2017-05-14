<?php
require_once(dirname(__DIR__) . '/vendor/autoload.php');

$forker = new \Jorker\JobForker(3);
$forker->allot(function() {
    for($i = 0; $i < 100; $i++) {
        yield ['i' => $i];
    }
})->run(function($job, \Jorker\Slave\Slave $slave) {
    // DO SOMETHING...
    $slave->logger()->info("use this way if you want print log.");
});