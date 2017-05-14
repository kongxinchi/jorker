<?php
require_once(dirname(__DIR__) . '/vendor/autoload.php');

$forker = new \Jorker\JobForker(3, [
    "reportInterval" => 1,                                 // 运行指定秒数后，对运行时统计进行报告
    "reportHandler" => function(\Jorker\MasterStatistics $statistics) {
        $fp = fopen('report.txt', 'a');
        fwrite($fp, date('Y-m-d H:i:s') . "|" . $statistics->formattedInfo() . PHP_EOL);
        fclose($fp);
    }
]);
$forker->allot(function() {
    $result = [];
    for($i = 0; $i < 100; $i++) {
        $result[] = ['i' => $i];
    }
    return $result;
})->run(function($job, \Jorker\Slave\Slave $slave) {
    // DO SOMETHING...
    usleep(100000);
    $slave->logger()->info("use this way if you want print log.");
});