<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2022-11-04 12:10:04
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-11-05 12:09:06
 */

namespace diandi\swrpc;

use diandi\swrpc\Tracer\TracerContext;
use Zipkin\Endpoint;
use Zipkin\Reporters\Http;
use Zipkin\Samplers\BinarySampler;
use Zipkin\TracingBuilder;

/**
 * Class LogicService
 *
 */
class LogicService
{
    protected $params;
    protected $module;
    protected $tracerUrl;
    protected $tracerContext;
    protected $clients = [];

    /**
     * @return static
     */
    public static function factory()
    {
        return new static();
    }

    /**
     * 初始化链路追踪器
     *
     * @param $func
     */
    public function initTracer($func)
    {
        $reporterUrl = $this->tracerUrl ?: 'http://127.0.0.1:9411/api/v2/spans';
        $endpoint = Endpoint::create($this->module);
        $reporter = new Http(['endpoint_url' => $reporterUrl]);
        $sampler = BinarySampler::createAsAlwaysSample();
        $tracing = TracingBuilder::create()
            ->havingLocalEndpoint($endpoint)
            ->havingSampler($sampler)
            ->havingReporter($reporter)
            ->build();
        $tracer = $tracing->getTracer();
        $span = $tracer->newTrace();
        $span->setName($func);
        $span->start();
        $span->finish();
        $tracer->flush();

        $ctx = $span->getContext();
        if ($this->tracerContext) {
            $this->tracerContext->setTraceID($ctx->getTraceId());
            $this->tracerContext->setParentID($ctx->getSpanId());
            $this->tracerContext->setReporterUrl($reporterUrl);
        } else {
            $this->tracerContext = TracerContext::create($ctx->getTraceId(), $ctx->getSpanId(), $reporterUrl);
        }
    }

    /**
     * @param $context
     * @return $this
     */
    public function setTracerContext($context)
    {
        $this->tracerContext = $context;
        return $this;
    }

    /**
     * @param $func
     * @return null
     */
    public function getTracerContext($func)
    {
        if (empty($this->tracerUrl)) {
            return null;
        }
        if (empty($this->tracerContext)) {
            $this->initTracer($func);
        }
        return $this->tracerContext;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function setParams(array $params)
    {
        $this->params = $params;
        return $this;
    }

    /**
     * @param string $url
     * @return static $this
     */
    public function setTracerUrl(string $url)
    {
        $this->tracerUrl = $url;
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setModule(string $name)
    {
        $this->module = $name;
        return $this;
    }
}
