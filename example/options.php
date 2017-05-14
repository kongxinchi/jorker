<?php
require_once(dirname(__DIR__) . '/vendor/autoload.php');

$forker = new \Jorker\JobForker(3, [
    "logger" => new \Jorker\Logger\SimpleEchoLogger(),     // 日志接口
    "logLevel" => \Psr\Log\LogLevel::DEBUG,                // 打印日志的最低等级
    "slaveMaxMemory" => 256*1024*1024,                     // 子进程最大内存，超出该内存终止子进程，终止后父进程会重新fork一个新的子进程
    "reportInterval" => 1,                                 // 运行指定秒数后，对运行时统计进行报告
    "stampFilePath" => "/tmp/stamp.dat"                    // 用于记录上一次中断时即将执行的数据
]);
$forker->allot(function() {
    for($i = 1; $i < 100; $i++) {
        yield ['i' => $i];
    }
})->run(function($job, \Jorker\Slave\Slave $slave) {
    // DO SOMETHING...
    $slave->logger()->info("use this way if you want print log.");
});
