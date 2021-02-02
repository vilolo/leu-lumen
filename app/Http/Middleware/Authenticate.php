<?php

namespace App\Http\Middleware;

use App\Tools\Utils;
use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;

class Authenticate
{
    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if ($this->auth->guard($guard)->guest()) {
//            return response('Unauthorized.', 401);
//            return Utils::res_error('请登录！', [], 401);
            return response()->json(Utils::res_error('请登录！', [], 401))->setEncodingOptions(JSON_UNESCAPED_UNICODE);
        }

        return $next($request);
    }
}
