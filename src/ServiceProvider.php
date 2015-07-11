<?php namespace Carrot;

use Carbon\Carbon;
use Illuminate\Routing\Router;
use Request;
use Config;
use Carrot\Support\ModuleProvider;
use \Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    protected $modules = [];

    public function register()
    {
        /**
         * @var ModuleProvider $module
         */
        $this->app->singleton('backend.menu', function () {
            return new BackendMenu();
        });

        foreach (Config::get('cms.modules', []) as $module) {
            $path = base_path("modules/".$module);
            $composer = json_decode(file_get_contents($path."/composer.json"), true);
            $composer['module']['path'] = $path;
            $providerClass = $composer['module']['namespace'].'ModuleProvider';
            $this->modules[] = $module = new $providerClass($this->app, $composer['module']);
            $module->register();
        }
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

        array_map(function (ModuleProvider $module) {
            $module->boot();
        }, $this->modules);

        if ($this->app->routesAreCached()) {
            $this->loadCachedRoutes();
        } else {
            $locale = Request::segment(1);
            $prefix = null;
            $domain = config('app.domain');
            if (in_array($locale, Config::get('app.other_locales'))) {
                \Lang::setLocale($locale);
                Carbon::setLocale($locale);
                $prefix = $locale;
            }
            array_map(function (ModuleProvider $module) use ($router, $prefix, $domain) {
                $group = $module->config('route', []) + [
                        'prefix'    => $prefix,
                        'as'        => $module->getName().'.',
                        'namespace' => $module->getNamespace().'Controllers'
                    ];
                if (isset($group['domain'])) {
                    $group['domain'] = str_replace('{$domain}', $domain, $group['domain']);
                }
                $router->group($group, function (Router $router) use ($module) {
                    $module->routes($router);
                });
            }, $this->modules);

            $backendGroup = config('cms.backend');
            $backendGroup['as'] = 'backend.';
            $backendGroup['middleware'] = ['auth'];
            $router->group($backendGroup, function (Router $router) {
                array_map(function (ModuleProvider $module) use ($router) {
                    $group = $module->config('backend');
                    $group['namespace'] = $module->getNamespace().'Controllers\\Backend';
                    $router->group($group, function (Router $router) use ($module) {
                        $module->backendRoutes($router);
                    });
                }, $this->modules);
            });

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