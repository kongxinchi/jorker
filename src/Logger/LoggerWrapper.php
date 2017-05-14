<?php
namespace Jorker\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * 日志接口包装，使调用者可以限制输出的日志等级
 * Class LoggerWrapper
 * @package Jorker\Logger
 */
class LoggerWrapper implements LoggerInterface
{

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected  $level;

    /**
     * LoggerWrapper constructor.
     * @param LoggerInterface $logger
     * @param string $level
     */
    public function __construct($logger, $level)
    {
        $this->logger = $logger;
        $this->level = $level;
    }

    protected function allow($level)
    {
        $rank = [
            LogLevel::EMERGENCY => 0,
            LogLevel::ALERT => 1,
            LogLevel::CRITICAL => 2,
            LogLevel::ERROR => 3,
            LogLevel::WARNING => 4,
            LogLevel::NOTICE => 5,
            LogLevel::INFO => 6,
            LogLevel::DEBUG => 7
        ];
        if (isset($rank[$level]) && isset($rank[$this->level])) {
            return $rank[$level] <= $rank[$this->level];
        }
        return false;
    }

    public function emergency($message, array $context = array())
    {
        if ($this->allow(LogLevel::EMERGENCY)) {
            $this->logger->emergency($message, $context);
        }
    }

    public function alert($message, array $context = array())
    {
        if ($this->allow(LogLevel::ALERT)) {
            $this->logger->alert($message, $context);
        }
    }

    public function critical($message, array $context = array())
    {
        if ($this->allow(LogLevel::CRITICAL)) {
            $this->logger->critical($message, $context);
        }
    }

    public function error($message, array $context = array())
    {
        if ($this->allow(LogLevel::ERROR)) {
            $this->logger->error($message, $context);
        }
    }

    public function warning($message, array $context = array())
    {
        if ($this->allow(LogLevel::WARNING)) {
            $this->logger->warning($message, $context);
        }
    }

    public function notice($message, array $context = array())
    {
        if ($this->allow(LogLevel::NOTICE)) {
            $this->logger->notice($message, $context);
        }
    }

    public function info($message, array $context = array())
    {
        if ($this->allow(LogLevel::INFO)) {
            $this->logger->info($message, $context);
        }
    }

    public function debug($message, array $context = array())
    {
        if ($this->allow(LogLevel::DEBUG)) {
            $this->logger->debug($message, $context);
        }
    }

    public function log($level, $message, array $context = array())
    {
        if ($this->allow($level)) {
            $this->logger->log($level, $message, $context);
        }
    }
}