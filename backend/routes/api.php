<?php
$router->get('/health',function(){ return response()->json(['success'=>true,'data'=>['module'=>env('SERVICE_MODULE','all')]]); });
if (in_array(env('SERVICE_MODULE','all'),['all','auth','sinhvien'])) {
    $router->post('/api/auth/login','AuthController@login');
    $router->get('/api/auth/me','AuthController@me');
    $router->post('/api/auth/logout','AuthController@logout');
    $router->get('/api/users','UserController@index');
    $router->get('/api/users/{id:[0-9]+}','UserController@show');
    $router->post('/api/users','UserController@store');
    $router->put('/api/users/{id:[0-9]+}','UserController@update');
    $router->delete('/api/users/{id:[0-9]+}','UserController@destroy');
}
foreach (['sinhvien'=>'SinhVien','detai'=>'DeTai','dangky'=>'DangKy'] as $path=>$controller) {
    if (!in_array(env('SERVICE_MODULE','all'),['all',$path])) continue;
    $router->get('/api/'.$path,$controller.'Controller@index');
    $router->get('/api/'.$path.'/{id:[0-9]+}',$controller.'Controller@show');
    $router->post('/api/'.$path,$controller.'Controller@store');
    $router->put('/api/'.$path.'/{id:[0-9]+}',$controller.'Controller@update');
    $router->delete('/api/'.$path.'/{id:[0-9]+}',$controller.'Controller@destroy');
}
