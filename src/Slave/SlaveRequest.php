<?php
namespace Jorker\Slave;

class SlaveRequest
{
    const TYPE_RUN = 'run';
    const TYPE_STOP = 'stop';

    public $type;

    /**
     * @var mixed
     */
    public $body;

    public static function run($body)
    {
        $ins = new SlaveRequest();
        $ins->type = self::TYPE_RUN;
        $ins->body = $body;
        return $ins;
    }

    public static function stop()
    {
        $ins = new SlaveRequest();
        $ins->type = self::TYPE_STOP;
        return $ins;
    }

    public function __toString()
    {
        return json_encode(['type' => $this->type, 'body' => serialize($this->body)]);
    }
}