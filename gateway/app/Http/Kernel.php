<?php
namespace App\Http;
class Kernel extends \Illuminate\Foundation\Http\Kernel
{
    protected $middleware = [\Illuminate\Http\Middleware\HandleCors::class];
    protected $middlewareGroups = ['api' => [\Illuminate\Routing\Middleware\SubstituteBindings::class]];
}
