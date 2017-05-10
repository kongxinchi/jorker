<?php
require_once(dirname(__DIR__) . '/vendor/autoload.php');

$a = "1\nb\nc";
echo serialize($a);
exit();
$manager = new \Jorker\JobForkerManager(3);
$manager->allot(function() {
    for($i=0; $i < 1; $i++) {
        yield ['i' => $i];
    }
})->run(function($job, \Jorker\Slave\Slave $slave) {
    // DO SOMETHING...
    throw new Exception("{$job['i']} throw exception");
}, function(\Jorker\Slave\SlaveResponse $response) {
    // FAIL HANDLE FUNCTION...
    echo (string)$response->error . PHP_EOL;
});