<?php


namespace App\Http\Controllers\Admin\v1;


use App\Http\Controllers\Admin\BaseAdminController;
use App\Tools\Utils;

class TestController extends BaseAdminController
{
    public function index()
    {
        echo 'admin test!!';
    }

    public function userInfo()
    {
        $user_info = auth()->user();
        return Utils::res_ok('ok', $user_info);
    }
}
