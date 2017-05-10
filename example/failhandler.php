<?php
require_once(dirname(__DIR__) . '/vendor/autoload.php');

$manager = new \Jorker\JobForkerManager(3);
$manager->allot(function() {
    for($i=0; $i < 5; $i++) {
        yield ['i' => $i];
    }
})->run(function($job, \Jorker\Slave\Slave $slave) {
    // DO SOMETHING...
    if ($job['i'] % 2 == 0) {
        throw new Exception("{$job['i']} throw exception");
    } else {
        new NOT_EXIST_CLASS();
    }
}, function($job, \Jorker\Slave\Error\SlaveErrorInterface $error) {
    // FAIL HANDLE FUNCTION...
    echo "===== JOB {$job['i']} Error Start =====" . PHP_EOL;
    echo $error->getTraceAsString() . PHP_EOL;
    echo "===== JOB {$job['i']} Error End =====" . PHP_EOL;
});