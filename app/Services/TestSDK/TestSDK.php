<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/6/30
 * Time: 17:17
 */

namespace App\Services\TestSDK;

$fileUtil = dirname(__FILE__) . DIRECTORY_SEPARATOR . "Common" . DIRECTORY_SEPARATOR . "Util.php";
include ($fileUtil);

class TestSDK
{
    public function test()
    {
        return ts();
    }
}