<?php namespace RabbitCMS\Carrot\Providers;

use Carbon\Carbon;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use RabbitCMS\Carrot\Facades\ModuleLoader;
use RabbitCMS\Carrot\Support\Layout;
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
        $this->app->singleton('rabbitcms.layout', function () {
            return new Layout();
        });

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
        $prefix = null;
        $locale = Request::segment(1);
        if (in_array($locale, Config::get('app.other_locales'), true)) {
            Lang::setLocale($locale);
            Carbon::setLocale($locale);
            $prefix = $locale;
        }

        if ($this->routesAreCached($prefix)) {
            $this->loadCachedRoutes($prefix);
        } else {
            $domain = config('app.domain');
            array_map(function (ModuleProvider $module) use ($router, $prefix, $domain) {
                $group = $module->config('route', []) + [
                        'prefix' => $prefix,
                        'as' => $module->getName() . '.',
                        'namespace' => $module->getNamespace() . 'Http\Controllers'
                    ];
                if (isset($group['domain'])) {
                    $group['domain'] = str_replace('{$domain}', $domain, $group['domain']);
                }
                $router->group($group, function (Router $router) use ($module) {
                    $module->routes($router);
                });
            }, ModuleLoader::getModules());
        }

        ModuleLoader::bootModules();
    }

    /**
     * Load the cached routes for the application.
     *
     * @param string $prefix
     * @return void
     */
    protected function loadCachedRoutes($prefix = null)
    {
        $this->app->booted(function () use ($prefix) {
            require $this->getCachedRoutesPath($prefix);
        });
    }

    /**
     * Determine if the application routes are cached.
     *
     * @param string $prefix
     * @return bool
     */
    public function routesAreCached($prefix = null)
    {
        return $this->app->make('files')->exists($this->getCachedRoutesPath($prefix));
    }

    /**
     * Get the path to the routes cache file.
     *
     * @param string $prefix
     * @return string
     */
    public function getCachedRoutesPath($prefix = null)
    {
        return $this->app->basePath() . '/bootstrap/cache/routes' . ($prefix ? "-$prefix" : '') . '.php';
    }
}