<?php


namespace App\Tools;


use Illuminate\Support\Str;

class Utils
{
    const RES_OK = 20000;
    const RES_ERROR = 100;

    public static function res_ok($msg = 'Success', $data = [])
    {
        return [
            'code' => self::RES_OK,
            'msg' => $msg,
            'data' => $data
        ];
    }

    public static function res_error($msg = 'Failed', $data = [], $code = self::RES_ERROR)
    {
        return [
            'code' => $code,
            'message' => $msg,
            'data' => $data
        ];
    }

    public static function create_token()
    {
        return Str::random(mt_rand(16, 64)); //生成随机token
    }
}
