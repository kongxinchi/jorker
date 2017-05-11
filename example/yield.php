<?php
require_once(dirname(__DIR__) . '/vendor/autoload.php');

$manager = new \Jorker\JobForkerManager(3);
$manager->allot(function() {
    for($i = 0; $i < 100; $i++) {
        yield ['i' => $i];
    }
})->run(function($job, \Jorker\Slave\Slave $slave) {
    // DO SOMETHING...
    $slave->logger()->info("use this way if you want print log.");
});