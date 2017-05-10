<?php
namespace Jorker\Logger;

use Psr\Log\AbstractLogger;

/**
 * 将日志输出到屏幕
 * Class SimpleEchoLogger
 * @package Gaia\Helpers\JobForker
 */
class SimpleEchoLogger extends AbstractLogger
{
    public function log($level, $message, array $context = array())
    {
        list($usec, $sec) = explode(" ", microtime());
        echo sprintf("[%s.%d](%s)%s", date('Y-m-d H:i:s', $sec), intval($usec*1000), strtoupper($level), $message) . PHP_EOL;
    }
}