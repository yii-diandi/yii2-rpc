<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2022-11-04 12:10:04
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-11-04 12:15:20
 */


namespace diandi\swrpc\Packer;


use diandi\swrpc\Request\Request;

/**
 * Interface PackerInterface
 *
 * @package Swrpc\Packer
 */
interface PackerInterface
{
    function pack(Request $data):string;
    function unpack(string $data);
}