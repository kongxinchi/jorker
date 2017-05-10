<?php
namespace Jorker\Slave\Error;

class SlaveError implements SlaveErrorInterface
{
    /**
     * @var array
     */
    protected $error;

    /**
     * @var string
     */
    protected $traceAsString;

    public function __construct($error)
    {
        $this->error = $error;
        $this->traceAsString = $this->buildTraceAsString();
    }

    protected function buildTraceAsString()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $length = count($trace);
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $file = isset($trace[$i]['file']) ? $trace[$i]['file'] : "";
            $line = isset($trace[$i]['line']) ? $trace[$i]['line'] : "";
            $class = isset($trace[$i]['class']) ? $trace[$i]['class'] : "";
            $type = isset($trace[$i]['type']) ? $trace[$i]['type'] : "";
            $function = isset($trace[$i]['function']) ? $trace[$i]['function'] : "";
            $args = isset($trace[$i]['args']) ? $trace[$i]['args'] : [];

            $str .= "#{$i}"
                . ($file ? " {$file} ({$line})" : "")
                ." {$class}{$type}{$function}"
                ."(". implode(', ', array_map('strval', $args)) .")"
                . PHP_EOL;
        }
        return $str;
    }

    public function getType()
    {
        return $this->error['type'];
    }

    public function getMessage()
    {
        return $this->error['message'];
    }

    public function getFile()
    {
        return $this->error['file'];
    }

    public function getLine()
    {
        return $this->error['line'];
    }

    public function getTraceAsString()
    {
        return $this->traceAsString;
    }

    public function __toString()
    {
        return "Type: {$this->getType()}, Message: {$this->getMessage()}, File: {$this->getFile()}, Line: {$this->getLine()}";
    }
}