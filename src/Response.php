<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2022-11-04 12:10:04
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-11-04 12:13:32
 */


namespace diandi\swrpc;


/**
 * Class Response
 *
 * @package Swrpc
 * @author wuzhc 202139 11:36:9
 */
class Response
{
    const RES_ERROR = 0;
    const RES_SUCCESS = 1;

    public string $msg;
    public int $code;
    public array $data;

    public function __construct($code, $msg, $data)
    {
        $this->data = $data;
        $this->code = $code;
        $this->msg = $msg;
    }

    public static function error($msg, $code = self::RES_ERROR, $data = []): Response
    {
        return new static($code, $msg, $data);
    }

    public static function success($data = [], $msg = 'success', $code = self::RES_SUCCESS): Response
    {
        return new static($code, $msg, $data);
    }
}