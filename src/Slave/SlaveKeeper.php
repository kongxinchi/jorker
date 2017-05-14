<?php
namespace Jorker\Slave;

use Jorker\MasterStatistics;
use Jorker\SocketCommunicate;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * 主进程对子进程状态的维护，对象在主进程中
 * Class SlaveKeeper
 * @package Jorker\Slave
 */
class SlaveKeeper
{
    const ST_IDLE = 0;         // 空闲，可以分配
    const ST_BUSY = 1;         // 执行中
    const ST_EXITING = 2;      // 正在退出

    /**
     * @var int
     */
    protected $status;

    /**
     * @var string
     */
    protected $pid;

    /**
     * @var resource
     */
    protected $socket;

    /**
     * @var callable
     */
    protected $failedCallback;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var MasterStatistics
     */
    protected $statistics;

    /**
     * 当前正在进行的任务
     * @var mixed
     */
    protected $job;

    public function __construct($pid, $socket, $logger, $statistics)
    {
        $this->pid = $pid;
        $this->socket = $socket;
        $this->status = self::ST_IDLE;
        $this->logger = $logger;
        $this->statistics = $statistics;
    }

    public function stop()
    {
        $request = SlaveRequest::stop();
        $this->logger->info("{Request -> {$this->pid}} {$request}");
        SocketCommunicate::send($this->socket, $request);
    }

    public function run($job)
    {
        $request = SlaveRequest::run($job);
        $this->setJob($job);
        $this->logger->debug("{Request -> {$this->pid}} {$request}");
        SocketCommunicate::send($this->socket, $request);
    }

    public function setJob($job)
    {
        $this->job = $job;
        return $this;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    public function setFailedCallback($cb)
    {
        $this->failedCallback = $cb;
        return $this;
    }

    public function handleIfResponse()
    {
        if ($this->status == self::ST_EXITING) {
            return false;
        } elseif ($this->status == self::ST_IDLE) {
            return true;
        }

         /* @var SlaveResponse $resp */
        $resp = SocketCommunicate::receive($this->socket);
        if ($resp) {

            if ($resp->ok) {
                $this->logger->debug("{Response <- {$this->pid}} {$resp}");
            } else {
                $this->logger->error("{Response <- {$this->pid}} {$resp}");
                if (is_callable($this->failedCallback)) {
                    call_user_func($this->failedCallback, $this->job, $resp->error);
                }
            }

            $this->setJob(null)->setFailedCallback(null)->setStatus($resp->exiting ? self::ST_EXITING : self::ST_IDLE);
            $this->statistics->update($resp);
            return $this->handleIfResponse();
        }
        return false;
    }

    public function closeSocket()
    {
        fclose($this->socket);
    }
}