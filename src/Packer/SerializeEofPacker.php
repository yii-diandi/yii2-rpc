<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2022-11-04 12:10:04
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-11-05 11:51:10
 */

namespace diandi\swrpc\Packer;

use diandi\swrpc\Request\Request;

/**
 * Class SerializeEofPacker
 *
 * @package Swrpc\Packer
 */
class SerializeEofPacker implements PackerInterface
{
    /**
     * @var string
     */
    protected $eof;

    public function __construct(array $options = [])
    {
        $this->eof = $options['settings']['package_eof'] ?? "\r\n";
    }

    public function pack(Request $data): string
    {
        return serialize($data);
    }

    public function unpack(string $data)
    {
        return unserialize($data);
    }
}
