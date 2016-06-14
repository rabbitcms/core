<?php

namespace RabbitCMS\Carrot\Http;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\View\View;
use Pingpong\Modules\Module;

abstract class ModuleController extends BaseController
{
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

    /**
     * @var integer|float
     */
    protected $cache = 0;

    /**
     * ModuleController constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->config = $app->make('config');
        if (method_exists($this, 'init')) {
            $this->app->call([$this, 'init']);
        }
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
     * Get module.
     *
     * @return Module
     */
    public function module()
    {
        static $module = null;
        if ($module === null) {
            $module = $this->app->make('modules')->get($this->module);
        }

        return $module;
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
        if ($this->cache > 0) {
            $response = \Route::prepareResponse($this->app->make('request'), $response);
            $response->headers->addCacheControlDirective('public');
            $response->headers->addCacheControlDirective('max-age', floor($this->cache * 60));
        }

        return $response;
    }

    /**
     * @param string $view
     * @param array  $data
     *
     * @return View
     */
    protected function view($view, array $data = [])
    {
        return $this->app->make('view')->make($this->viewName($view) . '::' . $view, $data, []);
    }

    /**
     * Get module view name.
     *
     * @param string $view
     *
     * @return string
     */
    protected function viewName($view)
    {
        return $this->module()->getLowerName() . '::' . $view;
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
}