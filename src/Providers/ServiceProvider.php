<?php namespace RabbitCMS\Carrot\Providers;

use Carbon\Carbon;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use RabbitCMS\Carrot\Facades\ModuleLoader;
use RabbitCMS\Carrot\Support\ModuleLoader as ModuleLoaderImpl;
use RabbitCMS\Carrot\Support\BackendMenu;
use RabbitCMS\Carrot\Support\ModuleProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     *
     */
    public function register()
    {
        $this->app->singleton('backend.menu', function () {
            return new BackendMenu();
        });

        $this->app->singleton('module.loader', function () {
            return new ModuleLoaderImpl($this->app);
        });

        ModuleLoader::loadModules(array_merge(['backend'], Config::get('cms.modules', [])));
        ModuleLoader::registerModules();
    }

    /**
     * Bootstrap any application services.
     *
     * @param  \Illuminate\Routing\Router $router
     *
     * @return void
     */
    public function boot(Router $router)
    {
        ModuleLoader::bootModules();

        if ($this->app->routesAreCached()) {
            $this->loadCachedRoutes();
        } else {
            $locale = Request::segment(1);
            $prefix = null;
            $domain = config('app.domain');
            if (in_array($locale, Config::get('app.other_locales'))) {
                Lang::setLocale($locale);
                Carbon::setLocale($locale);
                $prefix = $locale;
            }
            array_map(function (ModuleProvider $module) use ($router, $prefix, $domain) {
                $group = $module->config('route', []) + [
                        'prefix'    => $prefix,
                        'as'        => $module->getName().'.',
                        'namespace' => $module->getNamespace().'Http\Controllers'
                    ];
                if (isset($group['domain'])) {
                    $group['domain'] = str_replace('{$domain}', $domain, $group['domain']);
                }
                $router->group($group, function (Router $router) use ($module) {
                    $module->routes($router);
                });
            }, ModuleLoader::getModules());
        }
    }

    /**
     * Load the cached routes for the application.
     *
     * @return void
     */
    protected function loadCachedRoutes()
    {
        $this->app->booted(function () {
            require $this->app->getCachedRoutesPath();
        });
    }
}