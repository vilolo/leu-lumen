<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->group(['namespace' => 'Admin\\v1', 'prefix' => 'admin/v1'], function() use ($router){

    //需要登录接口
    $router->group(['middleware' => 'auth'], function() use ($router){
        $router->get('/bb','TestController@userInfo');
    });

    //无需登录接口
    $router->post('/login','AdminAccountController@login');
    $router->get('/tt','TestController@index');
});
