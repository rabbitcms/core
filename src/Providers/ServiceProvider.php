<?php namespace RabbitCMS\Carrot\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Pingpong\Modules\Module;

class ServiceProvider extends IlluminateServiceProvider
{
    public function boot(Router $router)
    {
        $router->group(
            [],
            function (Router $router) {
                array_map(
                    function (Module $module) use ($router) {
                        if (file_exists($path = $module->getExtraPath('Http/routes.php'))) {
                            //$autoload = $module->getComposerAttr('autoload');
                            //if (array_key_exists('psr-4', $autoload) && is_array($autoload['psr-4']) && count($autoload['psr-4']) > 0) {
                            //    $namespace = key($autoload['psr-4']).'\Http\Controllers';
                            //} else {
                            //    $namespace = '';
                            //}
                            require($path);
                        }
                    },
                    \Module::enabled()
                );
            }
        );
    }

    public function register()
    {
    }
}