<?php

namespace diandi\swrpcTests;

use diandi\swrpc\Request\SyncRequest;
use PHPUnit\Framework\TestCase;

/**
 * Class PackerTest
 * php74 ../phpunit.phar tests/ClientTest.php
 *
 * @package SwrpcTests
 */
class PackerTest extends TestCase
{
    /**
     * 注意：Request类属性和方法发生变化时，这个测试案例就没有意义了
     * @return string
     */
    public function testSerializeLengthPack()
    {
        $packer = new \Swrpc\Packer\SerializeLengthPacker();
        $result = $packer->pack(SyncRequest::create('SchoolService_getName', [1, 1]));
        $this->assertEquals('AAAAzU86MjU6IlN3cnBjXFJlcXVlc3RcU3luY1JlcXVlc3QiOjY6e3M6OToiACoAbWV0aG9kIjtzOjIxOiJTY2hvb2xTZXJ2aWNlX2dldE5hbWUiO3M6OToiACoAcGFyYW1zIjthOjI6e2k6MDtpOjE7aToxO2k6MTt9czo5OiIAKgBpc1N5bmMiO2I6MTtzOjExOiIAKgBpc1N5c3RlbSI7YjowO3M6ODoiACoAZXJyb3IiO047czoxNToiACoAdHJhY2VDb250ZXh0IjtOO30=', base64_encode($result));
        return base64_encode($result);
    }

    /**
     * @depends testSerializeLengthPack
     */
    public function testSerializeLenghtUnpack($value)
    {
        $expect = SyncRequest::create('SchoolService_getName', [1, 1]);
        $packer = new \Swrpc\Packer\SerializeLengthPacker();
        $result = $packer->unpack(base64_decode($value));
        $this->assertSame(serialize($expect), serialize($result));
    }

    /**
     * 注意：Request类属性和方法发生变化时，这个测试案例就没有意义了
     * @return string
     */
    public function testSerializeEofPack()
    {
        $packer = new \Swrpc\Packer\SerializeEofPacker();
        $result = $packer->pack(SyncRequest::create('SchoolService_getName', [1, 1]));
        $this->assertEquals('TzoyNToiU3dycGNcUmVxdWVzdFxTeW5jUmVxdWVzdCI6Njp7czo5OiIAKgBtZXRob2QiO3M6MjE6IlNjaG9vbFNlcnZpY2VfZ2V0TmFtZSI7czo5OiIAKgBwYXJhbXMiO2E6Mjp7aTowO2k6MTtpOjE7aToxO31zOjk6IgAqAGlzU3luYyI7YjoxO3M6MTE6IgAqAGlzU3lzdGVtIjtiOjA7czo4OiIAKgBlcnJvciI7TjtzOjE1OiIAKgB0cmFjZUNvbnRleHQiO047fQ==', base64_encode($result));
        return base64_encode($result);
    }

    /**
     * @depends testSerializeEofPack
     */
    public function testSerializeEofUnpack($value)
    {
        $expect = SyncRequest::create('SchoolService_getName', [1, 1]);
        $packer = new \Swrpc\Packer\SerializeEofPacker();
        $result = $packer->unpack(base64_decode($value));
        $this->assertSame(serialize($expect), serialize($result));
    }
}
