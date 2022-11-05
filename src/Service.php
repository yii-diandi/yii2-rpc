<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2022-11-04 12:10:04
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-11-05 11:57:24
 */

namespace diandi\swrpc;

use diandi\swrpc\Request\Request;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionMethod;

/**
 * Class Service
 *
 */
class Service
{
    /**
     * Undocumented variable
     * @var array
     * @date 2022-11-05
     * @example
     * @author Wang Chunsheng
     * @since
     */
    private $services = [];

    /**
     * Undocumented variable
     * @var array
     * @date 2022-11-05
     * @example
     * @author Wang Chunsheng
     * @since
     */
    protected $filers = [
        'factory',
        'initTracer',
        'setModule',
        'setTracerUrl',
        'setParams',
        'setTracerContext',
        'getTracerContext',
    ];

    /** @var LoggerInterface $logger */
    private $logger;

    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    /**
     * 注册服务实例
     *
     * @param $obj
     * @param $prefix
     * @return bool
     */
    public function addInstance($obj, $prefix = ''): bool
    {
        if (is_string($obj)) {
            $obj = new $obj();
        }
        if (!is_object($obj)) {
            $this->logger->error('Service is not an object.', ['service' => $obj]);
            return false;
        }
        if (!($obj instanceof LogicService)) {
            $this->logger->error('The Service does not inherit LogicService', ['service' => get_class($obj)]);
            return false;
        }
        $className = get_class($obj);
        $methods = get_class_methods($obj);
        foreach ($methods as $method) {
            if (in_array($method, $this->filers)) {
                continue;
            }
            if (strlen($prefix) > 0) {
                $key = $prefix . '_' . $className . '_' . $method;
            } else {
                $key = $className . '_' . $method;
            }
            $this->services[$key] = $className;
            $this->logger->info(sprintf('import %s => %s.', $key, $className));
        }

        return true;
    }

    /**
     * 获取服务
     *
     * @param $key
     * @return mixed|null
     */
    public function getService($key)
    {
        return $this->services[$key] ?? null;
    }

    /**
     * 获取所有服务
     * getServices
     *
     * @return array
     */
    public function getServices(): array
    {
        return $this->services;
    }

    /**
     * count
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->services);
    }

    /**
     * @param $key
     * @return bool
     */
    public function isExist($key): bool
    {
        return isset($this->services[$key]);
    }

    /**
     * 调用服务
     *
     * @param Request $request
     * @return Response
     * @throws \ReflectionException
     */
    public function call(Request $request): Response
    {
        if ($err = $request->getError()) {
            return Response::error($err);
        }

        $service = $this->getService($request->getMethod());
        if (!$service) {
            $this->logger->debug('service is not exist.', ['method' => $request->getMethod()]);
            return Response::error('service is not exist.');
        }

        $methodArr = explode('_', $request->getMethod());
        $methodName = array_pop($methodArr);
        $reflect = new ReflectionClass($service);
        $instance = $reflect->newInstanceArgs();
        if (!method_exists($instance, $methodName)) {
            $this->logger->debug('method is not exist.', ['method' => $request->getMethod()]);
            return Response::error(sprintf('%s method[%s] is not exist.', $service, $methodName));
        }

        $ctx = $request->getTraceContext();
        if ($ctx && method_exists($instance, 'setTracerContext')) {
            $instance->setTracerUrl($ctx->getReporterUrl())->setTracerContext($ctx);
        }

        try {
            $methodObj = new ReflectionMethod($reflect->getName(), $methodName);
            $result = $methodObj->invokeArgs($instance, $request->getParams());
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }

        return Response::success([
            'result' => $result,
        ]);
    }
}
