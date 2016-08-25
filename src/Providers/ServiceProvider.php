<?php

namespace RabbitCMS\Carrot\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use RabbitCMS\Carrot\Modules\Contracts\ModulesManager;
use RabbitCMS\Carrot\Modules\Manager;
use RabbitCMS\Carrot\Modules\Module;
use RabbitCMS\Carrot\Support\Facade\Modules;

class ServiceProvider extends IlluminateServiceProvider
{
    public function boot(Router $router, ModulesManager $modules)
    {
        $modules->enabled()->each(
            function (Module $module) use ($router) {
                if (file_exists($path = $module->getPath('Http/routes.php'))) {
                    require($path);
                }
            }
        );
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        AliasLoader::getInstance(['Modules' => Modules::class]);

        $this->registerConfig();
        $this->registerServices();

        $this->registerModules();
    }

    /**
     * Register config.
     */
    protected function registerConfig()
    {
        $configPath = realpath(__DIR__ . '/../config/config.php');

        $this->mergeConfigFrom($configPath, "carrot");

        $this->publishes([$configPath => config_path('carrot.php')]);
    }

    /**
     * Register the service provider.
     */
    protected function registerServices()
    {
        $this->app->singleton(
            ['modules'=> ModulesManager::class],
            function ($app) {
                return new Manager($app);
            }
        );
    }

    public function registerModules()
    {
        $this->app->booting(
            function (Application $app) {
                $app->make(ModulesManager::class)->register();
            }
        );
    }
}
