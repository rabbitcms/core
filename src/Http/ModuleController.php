<?php

namespace RabbitCMS\Carrot\Http;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;
use Pingpong\Modules\Module;

abstract class ModuleController extends BaseController
{
    use DispatchesJobs, ValidatesRequests;

    /**
     * @var Application $app
     */
    protected $app;

    /**
     * @var string
     */
    protected $module = '';

    /**
     * @var ConfigRepository
     */
    protected $config;

    protected $cache = false;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->config = $app->make('config');
    }

    public function module()
    {
        static $module = null;
        if ($module === null) {
            $module = $this->app->make('modules')->get($this->module);
        }

        return $module;
    }

    /**
     * Get the specified configuration value.
     *
     * @param  string $key
     * @param  mixed  $default
     *
     * @return mixed
     */
    public function config($key, $default = null)
    {
        return $this->config->get("module.{$this->module()->getLowerName()}.$key", $default);
    }

    /**
     * @param string $view
     * @param array  $data
     *
     * @return View
     */
    protected function view($view, array $data = [])
    {
        return app('view')->make($this->module()->getLowerName().'::'.$view, $data, []);
    }

    /**
     * @param string $path
     *
     * @return string
     */
    protected function asset($path)
    {
        return asset_module($path, $this->module()->getLowerName());
    }

    /**
     * Execute an action on the controller.
     *
     * @param  string $method
     * @param  array  $parameters
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function callAction($method, $parameters)
    {
        $response = parent::callAction($method, $parameters);
        if ($this->cache !== false) {
            $response = \Route::prepareResponse(\App::make('request'), $response);
            $response->headers->addCacheControlDirective('public');
            $response->headers->addCacheControlDirective('max-age', $this->cache * 60);
        }

        return $response;
    }
}