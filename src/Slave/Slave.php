<?php
namespace Jorker\Slave;

use Jorker\SocketCommunicate;
use Psr\Log\LoggerInterface;

/**
 * 子进程类，任务轮训
 * Class Slave
 * @package Jorker\Slave
 */
class Slave
{
    /**
     * @var callable
     */
    protected $handler;

    /**
     * @var int
     */
    protected $pid;

    /**
     * @var resource
     */
    protected $socket;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * 最大运行内存, 超过该内存子进程终止，单位字节
     * @var int
     */
    protected $maxMemory;

    /**
     * 该子进程已经执行了多少个任务
     * @var int
     */
    protected $count = 0;

    /**
     * 子进程的开始时间
     * @var int
     */
    protected $startTime;

    /**
     * 正在等待退出
     * @var bool
     */
    protected $exit = false;

    public function __construct($handler, $socket, $logger, $maxMemory)
    {
        $this->handler = $handler;
        $this->socket = $socket;
        $this->logger = $logger;
        $this->maxMemory = $maxMemory;
        $this->startTime = time();
        $this->pid = getmypid();
        register_shutdown_function([$this, 'shutdown']);
    }

    /**
     * @return bool
     */
    public function overMaxMemory()
    {
        return (memory_get_usage(true) > $this->maxMemory);
    }

    /**
     * @return SlaveInfo
     */
    public function buildSlaveInfo()
    {
        return new SlaveInfo(memory_get_usage(true), time() - $this->startTime, $this->count);
    }

    /**
     * @return LoggerInterface
     */
    public function logger()
    {
        return $this->logger;
    }

    public function loop()
    {
        while (!$this->exit) {
            /* @var \Jorker\Slave\SlaveRequest $request */
            if (!$request = SocketCommunicate::receive($this->socket)) {
                usleep(10000);  // 10毫秒
                continue;
            }

            if ($request->type == SlaveRequest::TYPE_RUN) {
                try {
                    $this->count ++;
                    call_user_func($this->handler, $request->body, $this);
                    $resp = SlaveResponse::complete();
                    $this->logger()->debug("{{$this->pid} -> Complete} {$this->buildSlaveInfo()}");
                } catch (\Exception $e) {
                    $resp = SlaveResponse::fail($e);
                    $this->logger()->error("{{$this->pid} -> Fail} {$this->buildSlaveInfo()}");
                }

                if ($this->exit = $this->overMaxMemory()) {
                    // 执行完成时如果超过内存限制
                    // 在返回中设置不再要求分配，防止在进程退出过程中，主进程还分配任务过来
                    $resp->setExiting(true);
                }

                SocketCommunicate::send($this->socket, $resp);

            } elseif ($request->type == SlaveRequest::TYPE_STOP) {
                $this->exit = true;
            }
        }
        fclose($this->socket);
    }

    public function shutdown()
    {
        if ($this->exit) {
            return;
        }

        $error = error_get_last();
        $resp = SlaveResponse::fail($error);
        $resp->setExiting(true);
        SocketCommunicate::send($this->socket, $resp);
        fclose($this->socket);
    }
}