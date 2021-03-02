<?php


namespace App\Tools;


use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator as IValidator;
use Illuminate\Support\Str;

class Utils
{
    const RES_OK = 20000;
    const RES_ERROR = 100;

    public static function res_ok($msg = 'Success', $data = [])
    {
        return [
            'code' => self::RES_OK,
            'message' => $msg,
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

    public static function validator(Request $request, array $rules, array $messages = []){
        $validator = IValidator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $messages = $validator->errors()->toArray();
            $msg = '';
            foreach ($messages as $key => $value) { $msg = $key.':'.$value[0]; break;}
            throw new ApiException($msg,422);
        }
    }

    public static function error_throw($msg, $code = 422)
    {
        throw new ApiException($msg, $code?$code:422);
    }
}
