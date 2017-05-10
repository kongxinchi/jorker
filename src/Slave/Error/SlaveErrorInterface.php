<?php
namespace Jorker\Slave\Error;


interface SlaveErrorInterface
{
    /**
     * @return string
     */
    public function getMessage();

    /**
     * @return string
     */
    public function getFile();

    /**
     * @return int
     */
    public function getLine();

    /**
     * @return string
     */
    public function getTraceAsString();
}