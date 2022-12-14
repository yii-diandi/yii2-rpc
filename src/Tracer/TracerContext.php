<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2022-11-04 12:10:04
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-11-04 12:13:20
 */


namespace diandi\swrpc\Tracer;


/**
 * 链路追踪上下文
 * Class TracerContext
 *
 */
class TracerContext
{
    protected $traceID;
    protected $parentID;
    protected $reporterUrl;

    public function __construct($traceID, $parentID, $reporterUrl)
    {
        $this->traceID = $traceID;
        $this->parentID = $parentID;
        $this->reporterUrl = $reporterUrl;
    }

    public static function create($traceID, $parentID, $reporterUrl)
    {
        return new static($traceID, $parentID, $reporterUrl);
    }

    public function setTraceID($traceID)
    {
        $this->traceID = $traceID;
    }

    public function setParentID($parentID)
    {
        $this->parentID = $parentID;
    }

    public function setReporterUrl($url)
    {
        $this->reporterUrl = $url;
    }

    public function getTraceID()
    {
        return $this->traceID;
    }

    public function getParentID()
    {
        return $this->parentID;
    }

    public function getReporterUrl()
    {
        return $this->reporterUrl;
    }
}
