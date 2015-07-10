<?php namespace Carrot\Support;

use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

abstract class ModuleProvider extends ServiceProvider
{
    protected $name;
    protected $path;
    protected $namespace;
    protected $moduleConfig;

    /**
     * Create a new service provider instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application $app
     * @param                                               $config
     */
    public function __construct($app, $config)
    {
        parent::__construct($app);
        $this->moduleConfig = $config;
        $this->path = $config['path'];
        $this->name = empty($config['name']) ? basename($this->path) : $config['name'];
        $this->namespace = $config['namespace'];
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    public function getName()
    {
        return $this->name;
    }

    public function config($key = null, $default = null)
    {
        if ($key === null) {
            return $this->moduleConfig;
        }

        return Arr::get($this->moduleConfig, $key, $default);
    }

    public function register()
    {
        $this->loadViewsFrom($this->path.'/views', $this->name);
    }

    public function boot()
    {
        $this->loadTranslationsFrom($this->path.'/lang', $this->name);
    }

    public function routes(Router $router)
    {

    }

    public function backendRoutes(Router $router)
    {

    }
}