<?php

namespace diandi\swrpcTests\services;

use diandi\swrpc\Exceptions\RpcException;
use diandi\swrpc\LogicService;
use diandi\swrpc\Register\Consul;
use diandi\swrpc\Request\SyncRequest;

/**
 * Class UserService
 *
 * @package SwrpcTests\services
 */
class UserService extends LogicService
{
    /**
     * @return UserService
     */
    public static function factory()
    {
        return parent::factory();
    }

    public function getName(): string
    {
        $register = new Consul();
        try {
            $userID = 1;
            $client = \Swrpc\Client::createBalancer('Class_Module', $register, \Swrpc\Client::STRATEGY_WEIGHT);
            $result = $client->send(SyncRequest::create('ClassService_getUserClass', [$userID], $this->getTracerContext(__FUNCTION__)));
        } catch (RpcException $e) {
            return $e->getMessage() . PHP_EOL;
        }

        return 'user:wuzhc， class:' . $result;
    }

    public function getAge(): int
    {
        return 30;
    }

    public function getFavoriteFood($prefix)
    {
        return $prefix . '的我喜欢吃苹果';
    }
}
