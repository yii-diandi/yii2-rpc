<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2022-11-04 12:10:04
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-11-04 12:12:37
 */


namespace diandi\swrpc\Register;


/**
 * Interface RegisterInterface
 *
 */
interface RegisterInterface
{
    function getName(): string;

    function register($module, $host, $port, $weight = 1);

    function unRegister($host, $port);

    function getServices(string $module): array;

    function getRandomService(string $module): Service;

    function getWeightService(string $module): Service;
}