<?php


namespace App\Http\Controllers\Admin\v1;


use App\Http\Controllers\Admin\BaseAdminController;
use App\Tools\Utils;

use Illuminate\Support\Facades\Config;

class TestController extends BaseAdminController
{
    public function index()
    {
//        echo env('APP_NAME');
//        print_r(config('app'));
//        print_r(Config::get('app'));
//        echo 'admin test!!!';

        $res = openssl_decrypt(hex2bin('88e70f47ec972d37edc236348073ef35'),'DES-CBC', 'MOJSzspV', OPENSSL_RAW_DATA, 'MOJSzspV');
        var_dump($res);
    }

    public function userInfo()
    {
        $user_info = auth()->user();
        return Utils::res_ok('ok', $user_info);
    }
}
