<?php

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

$router->get('/intro', 'InfoController@showGameInfo');

$router->post('/register', 'AuthController@register');
$router->post('/login', 'AuthController@login');

$router->group(['middleware'=>'auth:api'], function ($router) {
    $router->get('refreshToken', 'AuthController@refreshToken');

    $router->get('/', function () use ($router) {
        $output = ['text' => $router->app->version()];
        return response()->json($output);
    });

    $router->get('/me', 'AuthController@me');
    $router->get('/getTask/{missionUid}', 'TaskController@getTask');
    $router->get('/getReward', 'RewardController@getReward');
    $router->post('/verify/{vType}', 'VerifyController@verify');
});

// for convenient, create following endpoint to add data, need ADMIN_KEY
$router->post('/mission', 'MissionController@store');
$router->post('/task', 'TaskController@store');
$router->post('/reward', 'RewardController@store');
$router->post('/keypool', 'KeyPoolController@store');
