<?php

namespace RabbitCMS\Carrot\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class RouteServiceProvider extends BaseServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @param  \Illuminate\Routing\Router $router
     *
     * @return void
     */
    public function boot(Router $router)
    {
        $router->group([], function (Router $router) {
            array_map(function (\Pingpong\Modules\Module $module) use ($router) {
                if (file_exists($path = $module->getExtraPath('Http/routes.php'))) {
                    require($path);
                }
            }, \Module::enabled());
        });
    }

    public function register()
    {
        
    }
}