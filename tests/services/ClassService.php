<?php

namespace diandi\swrpcTests\services;

use diandi\swrpc\Exceptions\RpcException;
use diandi\swrpc\LogicService;
use diandi\swrpc\Register\Consul;
use diandi\swrpc\Request\SyncRequest;

/**
 * Class ClassService
 *
 * @package SwrpcTests\services
 */
class ClassService extends LogicService
{
    public function getUserClass($userID = 1): string
    {
        $register = new Consul();
        try {
            $classID = 111;
            $client = \Swrpc\Client::createBalancer('School_Module', $register, \Swrpc\Client::STRATEGY_WEIGHT);
            $result = $client->send(SyncRequest::create('SchoolService_getUserSchool', [
                $userID,
                $classID,
            ], $this->getTracerContext(__FUNCTION__)));
        } catch (RpcException $e) {
            return $e->getMessage() . PHP_EOL;
        }

        return '高一2班， school:' . $result;
    }
}
