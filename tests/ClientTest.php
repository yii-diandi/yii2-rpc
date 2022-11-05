<?php

namespace diandi\swrpcTests;

use diandi\swrpc\Client;
use diandi\swrpc\Request;

/**
 * 客户端单元测试
 * php74 ../phpunit.phar tests/ClientTest.php --debug
 * Class ClientTest
 *
 * @link http://www.phpunit.cn/manual/7.0/zh_cn/index.html
 */
class ClientTest extends BootTest
{
    /**
     * @return Client Client
     */
    public function testClientConnect(): Client
    {
        $client = Client::create('School_Module', getenv('RPC_SERVER_HOST'), getenv('RPC_SERVER_PORT'));
        $conn = $client->connect();
        $this->assertIsBool($conn->isConnected(), 'Client connect failure.');
        return $client;
    }

    /**
     * @depends testClientConnect
     * @param Client $client
     */
    public function testClientSyncRequest($client)
    {
        $request = Request\SyncRequest::create('SwrpcTests\services\SchoolService_getUserSchool', [1, 1]);
        $res = $client->send($request);
        $this->assertEquals('未来学校1', $res);
    }

    /**
     * @depends testClientConnect
     * @param $client
     */
    public function testClientAsyncRequest($client)
    {
        $request = Request\AsyncRequest::create('SwrpcTests\services\SchoolService_saveUserName', ['tony']);
        $res = $client->send($request);
        $this->assertEquals('success', $res);
        sleep(3);
        $this->assertFileExists('xxx.log', 'Async request failure.');
        $value = file_get_contents('xxx.log');
        $this->assertEquals('tony', $value);
        @unlink('xxx.log');
    }
}
