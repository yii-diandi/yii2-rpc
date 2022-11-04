<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2022-11-04 12:10:04
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-11-04 12:13:09
 */


namespace diandi\swrpc\Request;

/**
 * Class SyncRequest
 *
 */
class SyncRequest extends Request
{
    public function init()
    {
        $this->setSync(true);
        $this->setSystem(false);
    }
}