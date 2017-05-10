<?php
namespace Jorker\Slave\Error;

class SlaveError implements SlaveErrorInterface
{
    /**
     * @var array
     */
    protected $error;

    public function __construct($error)
    {
        $this->error = $error;
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
        return ""; // TODO
    }

    public function __toString()
    {
        return "Type: {$this->getType()}, Message: {$this->getMessage()}, File: {$this->getFile()}, Line: {$this->getLine()}";
    }
}