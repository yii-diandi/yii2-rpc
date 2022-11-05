<?php

namespace diandi\swrpcTests;

use diandi\swrpcTests\services\UserService;
use diandi\swrpc\Register\Consul;
use diandi\swrpc\Register\Service;
use diandi\swrpc\Request\Request;
use diandi\swrpc\Request\SyncRequest;
use diandi\swrpc\Server;
use Monolog\Logger;

/**
 * Class ServerTest
 * php74 ../phpunit.phar tests/ClientTest.php
 *
 * @package SwrpcTests
 */
class ServerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Undocumented function
     * @return void
     * @date 2022-11-05
     * @example
     * @author Wang Chunsheng
     * @since
     */
    public function testServerRegisterToConsul()
    {
        $res = shell_exec('netstat anp | grep ' . getenv('CONSUL_PORT') . ' | wc -l');
        $this->assertGreaterThanOrEqual(1, intval($res), 'Warning: Consul not started.');

        $consul = new Consul('http://' . getenv('CONSUL_HOST') . ':' . getenv('CONSUL_PORT'));
        $consul->register('test_module', '127.0.0.1', 8080);

        $isSuccess = false;
        $services = $consul->getServices('test_module');
        /** @var Service $service */
        foreach ($services as $service) {
            if ($service->getHost() == '127.0.0.1' && $service->getPort() == 8080) {
                $isSuccess = true;
                break;
            }
        }

        $this->assertIsBool($isSuccess);
        return $consul;
    }

    /**
     * @depends testServerRegisterToConsul
     * @param Consul $consul
     */
    public function testServerUnregisterFromConsul($consul)
    {
        $consul->unRegister('127.0.0.1', 8080);
        $isSuccess = true;
        $services = $consul->getServices('test_module');
        /** @var Service $service */
        foreach ($services as $service) {
            if ($service->getHost() == '127.0.0.1' && $service->getPort() == 8080) {
                $isSuccess = false;
                break;
            }
        }
        $this->assertIsBool($isSuccess);
    }

    /**
     * @return Server
     */
    public function testServerAddService()
    {
        $logger = new Logger('swprc');
        $server = new Server('School_Module', getenv('RPC_SERVER_HOST'), getenv('RPC_SERVER_PORT'), [], null, null, $logger);
        $server->addService(UserService::class);
        $key = UserService::class . '_getName';
        $value = $server->getService($key);
        $this->assertEquals(UserService::class, $value);
        return $server;
    }

    /**
     * @depends testServerAddService
     * @param $server
     */
    public function testServerAddMiddleware($server)
    {
        $request = SyncRequest::create('SwrpcTests\services\UserService_getFavoriteFood', ['肥胖']);
        $server->addMiddleware(function (Request $request, $next) {
            $request->setParams(['帅气']);
            return $next($request);
        });
        $func = $server->getRequestHandler();
        $result = $func($request);
        $this->assertEquals('帅气的我喜欢吃苹果', $result->data['result']);
    }
}
