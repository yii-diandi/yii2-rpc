<?php

namespace diandi\swrpcTests\services;

use diandi\swrpc\LogicService;

/**
 * Class SchoolService
 *
 * @package SwrpcTests\services
 */
class SchoolService extends LogicService
{
    public function getUserSchool($userID, $classID): string
    {
        return '未来学校' . $userID;
    }

    public function saveUserName($name)
    {
        file_put_contents('xxx.log', $name);
    }
}
