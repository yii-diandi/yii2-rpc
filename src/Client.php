<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2022-11-04 12:10:04
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-11-05 12:00:00
 */

namespace diandi\swrpc;

use diandi\swrpc\Exceptions\RpcException;
use diandi\swrpc\Packer\PackerInterface;
use diandi\swrpc\Packer\SerializeLengthPacker;
use diandi\swrpc\Register\RegisterInterface;
use diandi\swrpc\Register\Service;
use diandi\swrpc\Request\Request;
use Swoole\Client as SwClient;

/**
 * Class Client
 *
 */
class Client
{
    /**
     * Undocumented variable
     * @var int|string|array|object
     * @date 2022-11-05
     * @example
     * @author Wang Chunsheng
     * @since
     */
    protected $services = [];
    protected $connects = [];

    const STRATEGY_RANDOM = 1;
    const STRATEGY_WEIGHT = 2;

    protected $mode;
    protected $timeout = 3;

    /**
     * Undocumented variable
     * @var array
     * @date 2022-11-05
     * @example
     * @author Wang Chunsheng
     * @since
     */
    protected $options;

    /**
     * Undocumented variable
     * @var string
     * @date 2022-11-05
     * @example
     * @author Wang Chunsheng
     * @since
     */
    protected $module;

    /**
     * Undocumented variable
     * @var int
     * @date 2022-11-05
     * @example
     * @author Wang Chunsheng
     * @since
     */
    protected $strategy;

    /**
     * Undocumented variable
     * @var RegisterInterface|null
     * @date 2022-11-05
     * @example
     * @author Wang Chunsheng
     * @since
     */
    protected $register = null;

    /**
     * Undocumented variable
     * @var PackerInterface|null
     * @date 2022-11-05
     * @example
     * @author Wang Chunsheng
     * @since
     */
    protected $packer = null;

    /**
     * Undocumented variable
     * @var array
     * @date 2022-11-05
     * @example
     * @author Wang Chunsheng
     * @since
     */
    protected $defaultOptions = [
        'open_length_check' => true,
        'package_length_type' => 'N',
        'package_length_offset' => 0, //第N个字节是包长度的值
        'package_body_offset' => 4, //第几个字节开始计算长度
        'package_max_length' => 81920, //协议最大长度
    ];

    /**
     * Client constructor.
     *
     * @param string $module
     * @param array  $services
     * @param int    $mode
     * @param int    $timeout
     * @param array  $options
     */
    public function __construct(string $module, array $services, $mode = SWOOLE_SOCK_TCP, $timeout = 3, $options = [])
    {
        $this->module = $module;
        $this->services = $services;
        $this->mode = $mode;
        $this->timeout = $timeout;
        if (empty($options)) {
            $options = $this->defaultOptions;
        }
        $this->options = $options;

    }

    /**
     * @param string $module
     * @param string $host
     * @param int    $port
     * @param int    $mode
     * @param array  $options
     * @return Client
     */
    public static function create(
        string $module,
        string $host,
        int $port,
        $mode = SWOOLE_SOCK_TCP,
        $timeout = 3,
        $options = []
    ): Client {
        $service = Service::build($host, $port, 1);
        return new static($module, [$service], $mode, $timeout, $options);
    }

    /**
     * @param string            $module
     * @param RegisterInterface $register
     * @param int               $strategy
     * @param int               $mode
     * @param int               $timeout
     * @param array             $options
     * @return Client
     */
    public static function createBalancer(
        string $module,
        RegisterInterface $register,
        $strategy = self::STRATEGY_RANDOM,
        $mode = SWOOLE_SOCK_TCP,
        $timeout = 3,
        $options = []
    ): Client {
        $client = new static($module, [], $mode, $timeout, $options);
        $client->strategy = $strategy;
        $client->addRegister($register);
        return $client;
    }

    /**
     * @param RegisterInterface $register
     * @return $this
     */
    public function addRegister(RegisterInterface $register): Client
    {
        $this->register = $register;
        $this->services = $this->register->getServices($this->module);
        return $this;
    }

    /**
     * @param PackerInterface $packer
     * @return $this
     */
    public function addPacker(PackerInterface $packer): Client
    {
        $this->packer = $packer;
        return $this;
    }

    /**
     * @return SwClient
     * @throws RpcException
     */
    public function connect(): SwClient
    {
        $n = count($this->services);
        if ($n == 0) {
            throw new RpcException('No services available');
        }

        /** @var Service $service */
        if ($n == 1) { //单个服务节点
            $service = $this->services[0];
            $key = $service->getHost() . '_' . $service->getPort();
        } else { //多个服务节点
            $key = $this->getConnectKey();
        }

        if (isset($this->connects[$key]) && $this->connects[$key]->isConnected()) {
            return $this->connects[$key];
        }
        $client = new SwClient($this->mode ?: SWOOLE_SOCK_TCP);
        if (!$client->connect($service->getHost(), $service->getPort(), $this->timeout ?? 3)) {
            throw new RpcException("connect failed. Error: {$client->errCode}");
        }
        $client->set($this->options);
        $this->connects[$key] = $client;
        return $this->connects[$key];
    }

    /**
     * 发送请求
     *
     * @param Request $request
     * @return mixed
     * @throws RpcException
    202139 13:35:25
     */
    public function send(Request $request)
    {
        /** @var \Swoole\Client $conn */
        $conn = $this->connect();

        if (!$this->packer) {
            $this->packer = new SerializeLengthPacker([
                'package_length_type' => $options['package_length_type'] ?? 'N',
                'package_body_offset' => $options['package_body_offset'] ?? 4,
            ]);
        }

        $request->setModule($this->module);
        $conn->send($this->packer->pack($request));

        /** @var Response $response */
        $response = @unserialize($conn->recv());
        if (!($response instanceof Response)) {
            throw new RpcException('The server return type is not a Swrpc\Response');
        }
        if ($response->code == Response::RES_ERROR) {
            throw new RpcException($response->msg);
        }

        return $response->data['result'] ?? null;
    }

    /**
     * @return string
     */
    public function getConnectKey(): string
    {
        /** @var Service $service */
        if ($this->strategy == self::STRATEGY_RANDOM) {
            // $service = array_rand($this->services);
            return $service->getHost() . '_' . $service->getPort();
        } else {
            $totalWeight = 0;
            /** @var Service $service */
            foreach ($this->services as $service) {
                $totalWeight += $service->getWeight();
                $sort[] = $service->getWeight();
                $serviceArr[] = $service->toArray();
            }

            array_multisort($serviceArr, SORT_DESC, $sort);

            $start = 0;
            $rand = rand(1, $totalWeight);
            foreach ($serviceArr as $service) {
                if ($start + $service['weight'] >= $rand) {
                    return $service['host'] . '_' . $service['port'];
                }
                $start = $start + $service['weight'];
            }
        }
    }

    /**
     * 关闭客户端连接
     *
     * @return mixed
    2021310 9:16:46
     */
    public function close()
    {
        foreach ($this->connects as $connect) {
            $connect->close(true);
        }
    }

    /**
     * 刷新节点服务信息
     * 客户端使用长连接的情况下，需要起一个定时器来定时更新节点服务信息
     *
    2021313 18:24:23
     */
    public function refreshServices()
    {
        if ($this->register) {
            $this->services = $this->register->getServices($this->module);
            $this->connects = [];
        }
    }
}
