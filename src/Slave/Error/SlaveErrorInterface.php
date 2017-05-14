<?php
namespace Jorker\Slave\Error;

/**
 * 子进程错误封装接口
 * Interface SlaveErrorInterface
 * @package Jorker\Slave\Error
 */
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