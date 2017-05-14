<?php
namespace Jorker;

/**
 * 主进程与进程通信
 * Class SocketCommunicate
 * @package Jorker
 */
class SocketCommunicate
{
    public static function send($handler, $data)
    {
        $serialized = serialize($data);
        $length = strlen($serialized);
        $length = str_pad("{$length}", 8, '0', STR_PAD_LEFT);
        return fwrite($handler, $length . $serialized);
    }

    public static function receive($resource)
    {
        $recv = fread($resource, 8);
        if ($recv) {
            $length = intval(rtrim($recv));
            $serialized = fread($resource, $length);
            return unserialize($serialized);
        }
        return false;
    }
}