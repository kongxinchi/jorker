<?php
require_once(dirname(__DIR__) . '/vendor/autoload.php');

class CustomLogger extends \Psr\Log\AbstractLogger
{
    public function log($level, $message, array $context = array())
    {
        $fp = fopen('example.log', 'a');
        fwrite($fp, date('Y-m-d H:i:s') . "|{$level}|{$message}" . PHP_EOL);
        fclose($fp);
    }
}

$manager = new \Jorker\JobForkerManager(3, [
    "logger" => new CustomLogger(),
    'logLevel' => \Psr\Log\LogLevel::DEBUG
]);
$manager->allot(function() {
    for($i = 0; $i < 100; $i++) {
        yield ['i' => $i];
    }
})->run(function($job, \Jorker\Slave\Slave $slave) {
    // DO SOMETHING...
    $slave->logger()->info("use this way if you want print log.");
});