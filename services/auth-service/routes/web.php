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

$router->get('/', function () {
    return view('demo');
});

$router->post('/', 'AuthController@login');
$router->post('/login', 'AuthController@login');
$router->get('/auth', ['middleware' => 'jwt', 'uses' => 'AuthController@authenticate']);

$router->get('/hello', ['middleware' => 'jwt', function () {
    return response()->json([
        'message' => 'Hello World'
    ]);
}]);
