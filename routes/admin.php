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

//需要登录接口
$router->group(['middleware' => 'auth'], function() use ($router){
    $router->group(['namespace' => 'Admin\\v1', 'prefix' => 'admin/v1'], function() use ($router){
        $router->get('/bb','TestController@userInfo');
    });
});

//无需登录接口
$router->group(['namespace' => 'Admin\\v1', 'prefix' => 'admin/v1'], function() use ($router){
    $router->post('/login','AdminAccountController@login');
    $router->get('/test','TestController@index');
    $router->get('/getOrganizeData','SsppController@getOrganizeData');
    $router->get('/newOrganizeData','SsppController@newOrganizeData');
    $router->get('/showTemplate','SsppController@showTemplate');
    $router->post('/saveTemplate','SsppController@saveTemplate');
    $router->get('/getCategory','SsppController@getCategory');
    $router->post('/addCollect','SsppController@addCollect');
    $router->post('/delCollect','SsppController@delCollect');
    $router->post('/saveSearchLog','SsppController@saveSearchLog');
    $router->get('/showSearchLog','SsppController@showSearchLog');
    $router->post('/delSearchLog','SsppController@delSearchLog');
    $router->get('/allCategory','SsppController@allCategory');
    $router->get('/getDetail','SsppController@getDetail');
});
