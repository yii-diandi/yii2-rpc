<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2022-11-04 12:10:04
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-11-04 12:12:13
 */

namespace diandi\swrpc\Packer;

use diandi\swrpc\Request\Request;

/**
 * Class SerializeLengthPacker
 *
 * @package Swrpc\Packer
 */
class SerializeLengthPacker implements PackerInterface
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var int
     */
    protected $length;

    protected $defaultOptions
    = [
        'package_length_type' => 'N',
        'package_body_offset' => 4,
    ];

    public function __construct(array $options = [])
    {
        $options = array_merge($this->defaultOptions, $options['settings'] ?? []);

        $this->type = $options['package_length_type'];
        $this->length = $options['package_body_offset'];
    }

    public function pack(Request $data): string
    {
        $data = serialize($data);
        return pack($this->type, strlen($data)) . $data;
    }

    public function unpack(string $data)
    {
        $package = unpack('N', $data);
        $len = $package[1];

        //合并unserialize和substr，以减少内存拷贝 https://wenda.swoole.com/detail/107587
        if (function_exists('swoole_substr_unserialize')) {
            return swoole_substr_unserialize($data, $this->length, $len);
        }

        $data = substr($data, $this->length, $len);
        return unserialize($data);
    }
}
