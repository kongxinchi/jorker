<?php
namespace Jorker\Slave;


class SlaveInfo
{
    /**
     * 内存使用，单位字节
     * @var int
     */
    public $memory;

    /**
     * 持续执行时间，单位秒
     * @var int
     */
    public $elapse;

    /**
     * 持续执行任务个数
     * @var int
     */
    public $count;

    public function __construct($memory, $elapse, $count)
    {
        $this->memory = $memory;
        $this->elapse = $elapse;
        $this->count = $count;
    }

    public function toJSON()
    {
        return ['memory' => $this->memory, 'elapse' => $this->elapse, 'count' => $this->count];
    }

    public function __toString()
    {
        return json_encode($this->toJSON());
    }
}