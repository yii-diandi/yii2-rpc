<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2022-11-04 12:10:04
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-11-05 11:49:15
 */

namespace diandi\swrpc\Register;

/**
 * 注册中心服务
 * Class Service
 *
 */
class Service
{
    protected $host;
    protected $port;
    protected $weight;

    public function __construct($host, $port, $weight)
    {
        $this->host = $host;
        $this->port = $port;
        $this->weight = $weight;
    }

    public static function build($host, $port, $weight)
    {
        return new static($host, $port, $weight);
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getPort()
    {
        return $this->port;
    }

    /**
     * Undocumented function
     * @return int
     * @date 2022-11-05
     * @example
     * @author Wang Chunsheng
     * @since
     */
    public function getWeight()
    {
        return $this->weight;
    }

    public function toArray(): array
    {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'weight' => $this->weight,
        ];
    }
}
