<?php
namespace Jorker\Slave;

use Jorker\MasterStatistics;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * 主进程对子进程状态的维护，对象在主进程中
 * Class SlaveKeeper
 * @package Gaia\Helpers\JobForker
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
     * @var SlaveInfo
     */
    protected $slaveInfo;

    /**
     * @var MasterStatistics
     */
    protected $statistics;

    public function __construct($pid, $socket, $logger, $statistics)
    {
        $this->pid = $pid;
        $this->socket = $socket;
        $this->status = self::ST_IDLE;
        $this->logger = $logger;
        $this->statistics = $statistics;
    }

    /**
     * @param SlaveRequest $request
     */
    public function send($request)
    {
        // TODO 序列化函数会保留\n，需要想办法处理
        $logLevel = $request->type == SlaveRequest::TYPE_STOP ? LogLevel::INFO : LogLevel::DEBUG;
        $this->logger->log($logLevel, "{Request -> {$this->pid}} {$request}");
        fwrite($this->socket, serialize($request) . PHP_EOL);
    }

    public function stop()
    {
        $this->send(SlaveRequest::stop());
    }

    public function run($data)
    {
        $this->send(SlaveRequest::run($data));
    }

    /**
     * @return SlaveResponse|false
     */
    public function receive()
    {
        $line = fgets($this->socket);
        if ($line) {
            return unserialize(trim($line));
        }
        return false;
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

        $resp = $this->receive();
        if ($resp) {
            $logLevel = $resp->ok ? LogLevel::DEBUG : LogLevel::ERROR;
            $this->logger->log($logLevel, "{Response <- {$this->pid}} {$resp}");

            if (!$resp->ok && $this->failedCallback) {
                call_user_func($this->failedCallback, $resp);
            }

            $this->setFailedCallback(null)->setStatus($resp->exiting ? self::ST_EXITING : self::ST_IDLE);
            $this->slaveInfo = $resp->slaveInfo;
            $this->statistics->update($resp);
            return $this->handleIfResponse();
        }
        return false;
    }

    public function getSlaveInfo()
    {
        return $this->slaveInfo;
    }

    public function closeSocket()
    {
        fclose($this->socket);
    }
}