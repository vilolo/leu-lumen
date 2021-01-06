<?php


namespace App\Http\Controllers\Admin\v1;


use App\Http\Controllers\Admin\BaseAdminController;
use App\Models\User;
use App\Tools\Utils;
use Illuminate\Filesystem\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAccountController extends BaseAdminController
{
    public function login(Request $request)
    {
        $this->validate($request, [
            'username' => 'required',
            'password' => 'required',
        ], [
            'username.required' => '用户名必填',
            'password.required' => '密码必填'
        ]);

        //只允许非会员的账号登录
        $user = User::where('username', $request->get('username'))
            ->first();

        if (!$user) {
            return Utils::res_error('账户不存在');
        }

        if (!Hash::check($request->get('password'), $user->password)) {
            return Utils::res_error('密码错误');
        }

        $token = Utils::create_token();
        \Illuminate\Support\Facades\Cache::put('token:' . $token, $user->toArray(), 3600);
        return Utils::res_ok('登录成功', [
            'token' => $token,
            'user_info' => $user
        ]);
    }
}
