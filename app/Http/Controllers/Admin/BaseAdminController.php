<?php


namespace App\Http\Controllers\Admin;


use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator as IValidator;


class BaseAdminController extends Controller
{
    /**验证传递参数api
     * @param Request $request
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     * @throws ApiException
     */
//    protected function validated(Request $request, array $rules, array $messages = []){
//        $validator = IValidator::make($request->all(), $rules, $messages);
//        if ($validator->fails()) {
//            $this->errorBadRequest($validator);
//        }
//    }

    /**
     * 返回错误的请求
     * @param Validator $validator
     * @throws ApiException
     */
    protected function errorBadRequest(Validator $validator)
    {
        // github like error messages
        // if you don't like this you can use code bellow
        //
        //throw new ValidationHttpException($validator->errors());
        $messages = $validator->errors()->toArray();
        if ($messages) {
            $msg = array_shift($messages);
        }
        throw new ApiException($msg[0],422);
    }
}
