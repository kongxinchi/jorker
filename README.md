# Jorker
Run jobs with multi process. | 脚本多进程执行

## Features
* Execute script with multi process. 脚本多进程执行
* Continue last job that last time stopped. 支持中断续运行
* Memory overload protection. 内存过载保护
* Timing report progress. 定时报告进度
* Highly configurable. 高度可配置

## Setup
    composer require jorker/jorker

## Usage
    <?php
      require_once(dirname(__DIR__) . '/vendor/autoload.php');

      $manager = new \Jorker\JobForkerManager(3);
      $manager->allot(function() {
      
          // RETURN OR YIELD JOBS IN MASTER PROCESS...
          for($i = 0; $i < 100; $i++) {
              yield ['i' => $i];
          }
          
      })->run(function($job, \Jorker\Slave\Slave $slave) {
      
          // DO SOMETHING IN SUB PROCESS...
          $slave->logger()->info("use this way if you want print log {$job['i']}.");
          
      });
      
## Options
    \Jorker\JobForkerManager::__construct($limit, $options)
    
      @param int $limit | Sub process limit. 使用多少个子进程
      @param array $options | configs. 配置项
        [
          "logger" => new SimpleEchoLogger(),     // LoggerInterface. 日志接口
          "logLevel" => LogLevel::INFO,           // Print log which level greater or equal. 打印日志的最低等级
          "slaveMaxMemory" => 256*1024*1024,      // Sub process max memory, if over this value, master will stop this sub process and fork a new one. 子进程最大内存，超出该内存终止子进程，终止后父进程会重新fork一个新的子进程
          "reportInterval" => 600,                // Execute report handler every {reportInterval} seconds. 运行指定秒数后，对运行时统计进行报告
          "reportHandler" => functuin() {echo "REPORT";},  // Execute report handle. 回调函数，运行时统计报告
          "stampFilePath" => "/tmp/stamp.dat",    // File path that save last job when user CTRL+C stopped script. 用于记录上一次中断时，即将执行数据的保存路径
        ]
