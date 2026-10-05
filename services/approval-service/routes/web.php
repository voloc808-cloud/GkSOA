<?php
/** @var \Laravel\Lumen\Routing\Router $router */
$router->get('/health', function () {
    return response()->json(['status' => 'success', 'data' => ['service' => env('APP_NAME')], 'message' => 'Service is running.']);
});
