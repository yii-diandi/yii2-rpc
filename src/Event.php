<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2022-11-04 12:10:04
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-11-04 12:13:26
 */


namespace diandi\swrpc;

trait Event
{
    public function OnWorkerStart(\Swoole\Server $server, int $workerId)
    {
    }

    public function onStart(\Swoole\Server $server)
    {
    }

    public function onShutdown(\Swoole\Server $server)
    {
        
    }
}