<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2022-11-04 12:10:04
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-11-05 11:56:45
 */

namespace diandi\swrpc;

use diandi\swrpc\Middlewares\MiddlewareInterface;
use diandi\swrpc\Middlewares\TraceMiddleware;
use diandi\swrpc\Packer\PackerInterface;
use diandi\swrpc\Packer\SerializeLengthPacker;
use diandi\swrpc\Register\RegisterInterface;
use diandi\swrpc\Request\Request;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * Class Server
 *
 */
class Server
{
    use Event;

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
     * @var string
     * @date 2022-11-05
     * @example
     * @author Wang Chunsheng
     * @since
     */
    protected $host;

    /**
     * Undocumented variable
     * @var int
     * @date 2022-11-05
     * @example
     * @author Wang Chunsheng
     * @since
     */
    protected $port;

    /**
     * Undocumented variable
     * @var int
     * @date 2022-11-05
     * @example
     * @author Wang Chunsheng
     * @since
     */
    protected $weight = 1;

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

    /** @var PackerInterface $packer */
    protected $packer;

    /** @var Service $service */
    protected $service;

    /** @var LoggerInterface $logger */
    protected $logger;

    /** @var RegisterInterface $register */
    protected $register;

    /** @var \Swoole\Server $server */
    protected $server;

    /**
     * Undocumented variable
     * @var array
     * @date 2022-11-05
     * @example
     * @author Wang Chunsheng
     * @since
     */
    private $middlewares;

    public function __construct(
        string $module,
        string $host,
        int $port,
        array $options = [],
        $mode = SWOOLE_PROCESS,
        $socketType = SWOOLE_SOCK_TCP,
        LoggerInterface $logger = null
    ) {
        $this->module = $module;
        $this->host = $host;
        $this->port = $port;

        $this->setDefaultOptions($options);
        $this->setDefaultLogger($logger);
        $this->setCoreMiddleware();

        $this->service = new Service($this->logger);

        $server = new \Swoole\Server($host, $port, $mode ?: SWOOLE_PROCESS, $socketType ?: SWOOLE_SOCK_TCP);
        $server->set($this->options);
        $server->on('Start', [$this, 'onStart']);
        $server->on('Shutdown', [$this, 'onShutdown']);
        $server->on('WorkerStart', [$this, 'onWorkerStart']);
        $server->on('Connect', [$this, 'OnConnect']);
        $server->on('Receive', [$this, 'OnReceive']);
        $server->on('Close', [$this, 'OnClose']);
        $server->on('Task', [$this, 'OnTask']);
        $server->on('Finish', [$this, 'OnFinish']);
        $this->server = $server;
    }

    /**
     * 设置节点权重
     *
     * @param int $weight
     * @return Server
     */
    public function weight(int $weight): Server
    {
        $this->weight = $weight;
        return $this;
    }

    /**
     * 设置默认选项
     *
     * @param $options
     */
    protected function setDefaultOptions($options)
    {
        if (empty($options)) {
            $options = $this->defaultOptions;
        }

        $this->options = $options;

        //请求数量超过10000重启
        if (empty($this->options['max_request'])) {
            $this->options['max_request'] = 10000;
        }
        //默认task数量
        if (empty($this->options['task_worker_num'])) {
            $this->options['task_worker_num'] = swoole_cpu_num() * 2;
        }
        //task请求数超过10000则重启
        if (empty($this->options['task_max_request'])) {
            $this->options['task_max_request'] = 10000;
        }
        //10s没有数据传输就进行检测
        if (empty($this->options['tcp_keepidle'])) {
            $this->options['tcp_keepidle'] = 10;
        }
        //3s探测一次
        if (empty($this->options['tcp_keepinterval'])) {
            $this->options['tcp_keepinterval'] = 3;
        }
        //探测的次数，超过5次后还没回包close此连接
        if (empty($this->options['tcp_keepcount'])) {
            $this->options['tcp_keepcount'] = 5;
        }
    }

    /**
     * 设置默认日志处理器
     *
     * @param LoggerInterface|null $logger
     */
    protected function setDefaultLogger(LoggerInterface $logger = null)
    {
        if (empty($logger)) {
            $logger = new Logger('swrpc');
            $logger->pushHandler(new StreamHandler(STDOUT, Logger::DEBUG));
        }
        $this->logger = $logger;
    }

    /**
     * 设置核心中间件
     *
     */
    protected function setCoreMiddleware()
    {
        $this->middlewares[] = TraceMiddleware::class;
    }

    /**
     * 添加中间件，支持匿名函数和实现类
     * addMiddleware
     *
     * @param mixed ...$middlewares
     */
    public function addMiddleware(...$middlewares)
    {
        foreach ($middlewares as $middleware) {
            if (is_string($middleware) && class_exists($middleware)) {
                $middleware = new $middleware();
            }
            if (!($middleware instanceof \Closure) && !($middleware instanceof MiddlewareInterface)) {
                $this->logger->warning('Skip illegal Middleware.');
                continue;
            }
            $this->middlewares[] = $middleware;
        }
    }

    /**
     * 添加服务
     * addService
     *
     * @param        $service
     * @param string $prefix
     * @return Server
     */
    public function addService($service, $prefix = ''): Server
    {
        $this->service->addInstance($service, $prefix);
        return $this;
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public function getService($key)
    {
        return $this->service->getService($key);
    }

    /**
     * 注册发现中心
     *
     * @param $register
     * @return Server
     */
    public function addRegister($register): Server
    {
        $this->register = $register;
        return $this;
    }

    /**
     * 添加日志处理器
     *
     * @param $logger
     */
    public function addLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * 添加包解析器
     *
     * @param $packer
     */
    public function addPacker($packer)
    {
        $this->packer = $packer;
    }

    /**
     * 注册服务到consul
     * onWorkerStart 和 onStart 回调是在不同进程中并行执行的，不存在先后顺序
     *
     * @param \Swoole\Server $server
     */
    public function onStart(\Swoole\Server $server)
    {
        if ($this->register) {
            $this->logger->info(sprintf('Register server[%s:%d] to %s.', $this->host, $this->port, $this->register->getName()));
            $this->register->register($this->module, $this->host, $this->port, $this->weight);
        }
    }

    /**
     * 注销服务
     * 强制 kill 进程不会回调 onShutdown
     * 需要使用 kill -15 来发送 SIGTERM 信号到主进程才能按照正常的流程终止
     *
     * @param \Swoole\Server $server
     */
    public function onShutdown(\Swoole\Server $server)
    {
        if ($this->register) {
            $this->logger->info(sprintf('UnRegister server[%s:%d] from register.', $this->host, $this->port));
            $this->register->unRegister($this->host, $this->port);
        }
    }

    /**
     * server接收请求
     *
     * @param \Swoole\Server $server
     * @param                $fd
     * @param                $reactor_id
     * @param                $data
     * @return mixed
     */
    public function onReceive(\Swoole\Server $server, $fd, $reactor_id, $data)
    {
        /** @var Request $request */
        $request = $this->packer->unpack($data);
        //系统请求
        if ($request->isSystem()) {
            return $server->send($fd, serialize($this->doSystemRequest($request)));
        }
        //同步请求
        if ($request->isSync()) {
            return $server->send($fd, serialize($this->doRequest($request)));
        }
        //异步请求
        $server->task($request);
        return $server->send($fd, serialize(Response::success(['result' => 'success'])));
    }

    /**
     * 执行请求
     *
     * @param Request $request
     * @return Response
     */
    public function doRequest(Request $request): Response
    {
        try {
            $handler = $this->getRequestHandler();
        } catch (\ReflectionException $e) {
            return Response::error($e->getMessage());
        }

        $response = $handler($request);
        if (!($response instanceof Response)) {
            $msg = 'The middleware must return the response type';
            $this->logger->error($msg);
            $response = Response::error($msg);
        }

        return $response;
    }

    /**
     * 系统请求
     *
     * @param Request $request
     * @return Response
     */
    public function doSystemRequest(Request $request): Response
    {
        if ($request->getMethod() == 'stats') {
            return Response::success(['result' => $this->server->stats()]);
        } else {
            return Response::error($request->getMethod() . ' is not supported');
        }
    }

    /**
     * @return mixed
     * @throws \ReflectionException
     */
    public function getRequestHandler()
    {
        return array_reduce(array_reverse($this->middlewares), function ($stack, $next) {
            return function ($request) use ($stack, $next) {
                if ($next instanceof \Closure) {
                    return $next($request, $stack);
                } elseif (is_string($next) && class_exists($next)) {
                    return (new $next())->handle($request, $stack);
                } else {
                    return $next->handle($request, $stack);
                }
            };
        }, function ($request) {
            return $this->service->call($request);
        });
    }

    /**
     * 异步处理请求
     *
     * @param $server
     * @param $taskID
     * @param $reactorID
     * @param $data
     * @return Response
     */
    public function OnTask($server, $taskID, $reactorID, $data): Response
    {
        $this->logger->debug('AsyncTask: Start', ['taskID' => $taskID]);
        return $this->doRequest($data);
    }

    /**
     * 完成异步任务回调
     *
     * @param $server
     * @param $taskID
     * @param $data
     */
    public function OnFinish($server, $taskID, $data)
    {
        $this->logger->debug('AsyncTask: Finish', ['taskID' => $taskID, 'data' => $data]);
    }

    /**
     * OnClose
     *
     * @param $server
     * @param $fd
     */
    public function OnClose($server, $fd)
    {
        $this->logger->debug('Client: Close');
    }

    /**
     * OnConnect
     *
     * @param $server
     * @param $fd
     */
    public function OnConnect($server, $fd)
    {
        $this->logger->debug('Client: Connect.');
    }

    /**
     * start
     *
     */
    public function start(): bool
    {
        //可用服务数量
        if ($this->service->count() == 0) {
            $this->logger->error('There is no service available.');
            return false;
        }
        //默认使用固定包头+包体方式解决粘包问题
        if (empty($this->packer)) {
            $this->packer = new SerializeLengthPacker([
                'package_length_type' => $this->options['package_length_type'] ?? 'N',
                'package_body_offset' => $this->options['package_body_offset'] ?? 4,
            ]);
        }

        $this->logger->info(sprintf('Rpc server[%s:%s] start.', $this->host, $this->port));
        $this->server->start();
        return true;
    }
}
