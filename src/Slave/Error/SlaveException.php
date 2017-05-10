<?php
namespace Jorker\Slave\Error;

class SlaveException implements SlaveErrorInterface
{
    protected $className;

    protected $code;

    protected $message;
    
    protected $file;
    
    protected $line;
    
    protected $traceAsString;

    /**
     * SlaveException constructor.
     * @param \Exception $exception
     */
    public function __construct($exception)
    {
        $this->className = get_class($exception);
        $this->code = $exception->getCode();
        $this->message = $exception->getMessage();
        $this->file = $exception->getFile();
        $this->line = $exception->getLine();
        $this->traceAsString = $exception->getTraceAsString();
    }

    public function getClassName()
    {
        return $this->className;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function getLine()
    {
        return $this->line;
    }

    public function getTraceAsString()
    {
        return $this->traceAsString;
    }

    public function __toString()
    {
        return "ClassName: {$this->getClassName()}, Code: {$this->getCode()}, Message: {$this->getMessage()}, File: {$this->getFile()}, Line: {$this->getLine()}";
    }
}