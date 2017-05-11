<?php
namespace Jorker;

use Jorker\Logger\LoggerWrapper;
use Jorker\Logger\SimpleEchoLogger;
use Jorker\Slave\Slave;
use Jorker\Slave\SlaveKeeper;
use Psr\Log\LogLevel;

/**
 * 多进程任务执行
 * Class JobForkerManager
 * @package Gaia\Helpers\JobForker
 */
class JobForkerManager
{
    /**
     * 日志接口
     * @var LoggerWrapper
     */
    protected $logger;

    /**
     * 使用多少个子进程
     * @var int
     */
    protected $limit = 1;

    /**
     * 当前是否在子进程中
     * @var bool
     */
    protected $isForked = false;

    /**
     * 是否要求进程中止
     * @var bool
     */
    protected $stop = false;

    /**
     * 进程数据
     * @var SlaveKeeper[]
     */
    protected $pool = [];

    /**
     * 需要执行的数据
     * @var array|\Iterator
     */
    protected $jobs = [];

    /**
     * 执行的函数
     * @var callable
     */
    protected $executeHandler = null;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var MasterStatistics
     */
    protected $statistics;

    /**
     * JobForkerManager constructor.
     * @param int $limit 进程数量
     * @param array $options
     * options e.g.
     * [
     *     "logger" => new SimpleEchoLogger(),     // 日志接口
     *     "logLevel" => LogLevel::INFO,           // 打印日志的最低等级
     *     "slaveMaxMemory" => 256*1024*1024,      // 子进程最大内存，超出该内存终止子进程，终止后父进程会重新fork一个新的子进程
     *     "reportInterval" => 600,                // 运行指定秒数后，对运行时统计进行报告
     *     "reportHandler" => null,                // 回调函数，运行时统计报告
     *     "stampFilePath" => "/tmp/stamp.dat",    // 用于记录上一次中断时即将执行的数据
     * ]
     */
    public function __construct($limit = 1, $options = [])
    {
        if (!isset($options['logger'])) {
            $options['logger'] = new SimpleEchoLogger();
        }
        $options += [
            'logLevel' => LogLevel::INFO,
            'slaveMaxMemory' => 256*1024*1024,
            'reportInterval' => 600,
            'reportHandler' => [$this, 'defaultReportHandler'],
            'stampFilePath' => "/tmp/" . basename($_SERVER['PHP_SELF']) . '.stamp'
        ];

        $this->limit = $limit;
        $this->logger = new LoggerWrapper($options['logger'], $options['logLevel']);
        $this->statistics = new MasterStatistics($options['reportHandler'], $options['reportInterval']);
        $this->options = $options;
        $this->registerSignalHandler();
    }

    protected function registerSignalHandler()
    {
        pcntl_signal(SIGINT, function() {
            echo "STOPPING" . PHP_EOL;
            $this->stop = true;
        });
    }

    protected function saveLastStamp($job)
    {
        $filePath = $this->options['stampFilePath'];
        if (!$filePath) {
            return;
        }

        $dir = dirname($filePath);
        is_dir($dir) || @mkdir($dir, 0666, true);
        if (false === file_put_contents($filePath, serialize($job))) {
            $this->logger->warning("{Stamp} Save stamp file failed: {$filePath}");
        }
    }

    protected function loadLastStamp()
    {
        $filePath = $this->options['stampFilePath'];

        if($filePath && file_exists($filePath)) {
            if ($job =  @unserialize(file_get_contents($filePath))) {
                return $job;
            }
        }
        return null;
    }

    /**
     * 默认的统计报告函数
     * @param MasterStatistics $statistics
     */
    public function defaultReportHandler($statistics)
    {
        $this->logger->info("{Statistics} " . $statistics->formattedInfo());
    }

    /**
     * 设置分发函数
     * @param callable $handler
     * @return $this
     */
    public function allot(callable $handler)
    {
        $this->jobs = call_user_func($handler, $this->loadLastStamp());
        $this->statistics->setTotalCountByDatas($this->jobs);
        return $this;
    }

    /**
     * 设置执行函数，执行这个函数时会真正开始执行
     * @param callable $runHandler
     * @param callable|null $failedCallback
     */
    public function run(callable $runHandler, callable $failedCallback= null)
    {
        $this->executeHandler = $runHandler;

        declare(ticks = 1) {
            foreach ($this->jobs as $job) {
                if ($this->stop) {
                    $this->saveLastStamp($job);
                    break;
                }

                $keeper = $this->getAvailableSlave();

                // 子进程会走到这里，直接退出
                if ($this->isForked) {
                    return;
                }

                $keeper->setFailedCallback($failedCallback)
                    ->setStatus(SlaveKeeper::ST_BUSY)
                    ->run($job);
            }
        }
        $this->wait();
    }

    public function fork()
    {
        // 创建全双工Socket连接，并设置为不阻塞
        $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        stream_set_blocking($sockets[0], 0);
        stream_set_blocking($sockets[1], 0);

        $pid = pcntl_fork();
        if ($pid == -1) {
            //错误处理：创建子进程失败时返回-1.
            throw new \Exception('fork process failed');
        } else if ($pid) {
            // 父进程会得到子进程号，所以这里是父进程执行的逻辑
            fclose($sockets[0]);
            $this->pool[$pid] = new SlaveKeeper($pid, $sockets[1], $this->logger, $this->statistics);
            $this->logger->info("{Fork -> {$pid}}");
        } else {
            // 子进程逻辑
            fclose($sockets[1]);
            $this->isForked = true;
            $slave = new Slave($this->executeHandler, $sockets[0], $this->logger, $this->options['slaveMaxMemory']);
            $slave->loop();
        }
    }

    /**
     * 有进程退出时的处理
     * @param bool $block 是否阻塞等待
     */
    public function handleIfSlaveExit($block = false)
    {
        if ($pid = pcntl_wait($status, $block ? 0 : WNOHANG)) {
            if (!isset($this->pool[$pid])) {
                if ($pid > 0) {
                    $this->logger->warning("{Exit <- {$pid}} But this slave not in the pool");
                }
                return;
            }

            // 进程结束时，有可能还有响应没有处理完
            $this->pool[$pid]->handleIfResponse();

            // 关闭连接
            $this->pool[$pid]->closeSocket();

            // 打印进程退出日志
            $this->logger->info("{Exit <- {$pid}} ");

            unset($this->pool[$pid]);
        }
    }

    /**
     * 当子进程池不满时，fork新的进程
     */
    public function forkIfPoolNotFull()
    {
        // 填满子进程池的空缺
        while (!$this->isForked && count($this->pool) < $this->limit) {
            $this->fork();
        }
    }


    /**
     * 获取空闲的Slave，函数阻塞直到有Slave空闲
     * @return SlaveKeeper
     */
    public function getAvailableSlave()
    {
        while (true) {

            // 检查是否有子进程退出
            $this->handleIfSlaveExit();

            // 池不满时fork新子进程
            $this->forkIfPoolNotFull();

            if ($this->isForked) {
                return null;
            }

            $result = null;
            foreach ($this->pool as $keeper) {
                if ($keeper->handleIfResponse() && is_null($result)) {
                    $result = $keeper;
                }
            }

            if (!is_null($result)) {
                return $result;
            }
            usleep(10000);   // 10毫秒
        }

        return null;
    }

    /**
     * 主进程中等待子进程全部结束
     */
    public function wait()
    {
        foreach ($this->pool as $keeper) {
            $keeper->stop();
        }
        while (count($this->pool) > 0) {
            $this->handleIfSlaveExit(true);
        }
        $this->statistics->report();
    }
}