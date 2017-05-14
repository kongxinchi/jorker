<?php
namespace Jorker\Logger;

use Psr\Log\AbstractLogger;

/**
 * 默认的日志接口实现，将日志简单的输出到屏幕
 * Class SimpleEchoLogger
 * @package Jorker\Logger
 */
class SimpleEchoLogger extends AbstractLogger
{
    public function log($level, $message, array $context = array())
    {
        list($usec, $sec) = explode(" ", microtime());
        echo sprintf("[%s.%d](%s)%s", date('Y-m-d H:i:s', $sec), intval($usec*1000), strtoupper($level), $message) . PHP_EOL;
    }
}