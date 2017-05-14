<?php
namespace Jorker;

use Jorker\Slave\SlaveResponse;

class MasterStatistics
{
    /**
     * 报告执行函数
     * @var callable
     */
    protected $handler;

    /**
     * 报告的时间间隔，默认10分钟
     * @var int
     */
    protected $reportInterval;

    /**
     * 开始时间
     * @var int
     */
    public $startTime;

    /**
     * 最近一次报告的时间
     * @var int
     */
    public $lastReportTime;

    /**
     * 共需要执行的个数，如果是迭代器，这个值为-1
     * @var int
     */
    public $totalCount;

    /**
     * 执行了的个数
     * @var int
     */
    public $executedCount = 0;

    /**
     * 执行失败个数
     * @var int
     */
    public $failedCount = 0;

    public function __construct($handler, $reportInterval = 600)
    {
        $this->handler = $handler;
        $this->reportInterval = $reportInterval;
        $this->startTime = time();
        $this->lastReportTime = $this->startTime;
    }

    /**
     * 根据需要执行的数据量设置总共的个数
     * @param array|\Traversable $datas
     */
    public function setTotalCountByDatas($datas)
    {
        $this->totalCount = (is_array($datas) || $datas instanceof \Countable) ? count($datas) : -1;
    }

    /**
     * 根据子进程的返回，更新统计信息
     * @param SlaveResponse $response
     */
    public function update($response)
    {
        $this->executedCount ++;
        if (!$response->ok) {
            $this->failedCount ++;
        }
        $this->reportIfNecessary();
    }

    /**
     * 超过报告间隔时间时，进行报告
     */
    protected function reportIfNecessary()
    {
        $now = time();
        if ($now - $this->lastReportTime >= $this->reportInterval) {
            $this->report();
            $this->lastReportTime = $now;
        }
    }

    public function report()
    {
        call_user_func($this->handler, $this);
    }

    /**
     * 持续执行了的时间
     * @return int
     */
    public function elapse()
    {
        return time() - $this->startTime;
    }

    /**
     * 格式化后的持续时间
     * @return string
     */
    public function formattedElapse()
    {
        $total = $this->elapse();
        return sprintf("%02d:%02d:%02d", intval($total / 3600), intval($total / 60) % 60, $total % 60);
    }

    /**
     * 还需要多少秒执行完成
     * @return int
     */
    public function estimated()
    {
        if ($this->totalCount == -1) {
            return -1;
        }
        return intval($this->elapse() * $this->executedCount / $this->totalCount);
    }

    /**
     * 格式化后的预计时长
     * @return string
     */
    public function formattedEstimated()
    {
        $total = $this->estimated();
        return sprintf("%02d:%02d:%02d", intval($total / 3600), intval($total / 60) % 60, $total % 60);
    }

    /**
     * 格式化后的统计信息
     * @return string
     */
    public function formattedInfo()
    {
        if ($this->totalCount > 0) {
            $info = "Elapse: {$this->formattedElapse()}"
                . ", Executed: {$this->executedCount}/{$this->totalCount}"
                . ", Failed: {$this->failedCount}"
                . ", Rate: " . round($this->executedCount*100/$this->totalCount, 1) . "%"
                . ", Estimated: {$this->formattedEstimated()}";
        } else {
            $info = "Elapse: {$this->formattedElapse()}"
                . ", Executed: {$this->executedCount}"
                . ", Failed: {$this->failedCount}";
        }
        return $info;
    }
}