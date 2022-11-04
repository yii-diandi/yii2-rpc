<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2022-11-04 12:10:04
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-11-04 12:11:45
 */


namespace diandi\swrpc\Middlewares;


use Closure;
use diandi\swrpc\Request\Request;
use diandi\swrpc\Response;


interface MiddlewareInterface
{
    function handle(Request $request, Closure $next): Response;
}