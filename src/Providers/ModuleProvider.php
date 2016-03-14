<?php

namespace RabbitCMS\Carrot\Providers;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

abstract class ModuleProvider extends IlluminateServiceProvider
{
    /**
     * @var \Pingpong\Modules\Module
     */
    protected $module;

    /**
     * @var \Pingpong\Modules\Repository
     */
    protected $moduleManager;

    /**
     * Fetch module name
     *
     * @return string
     */
    abstract protected function name();

    public function __construct(\Illuminate\Contracts\Foundation\Application $app)
    {
        parent::__construct($app);
        $this->moduleManager = $this->app->make('modules');
        $this->module = $this->moduleManager->get($this->name());
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->registerConfig();
        $this->registerTranslations();
        $this->registerViews();
        $path = $this->moduleManager->getAssetsPath().'/'.$this->module->getLowerName();
        $public = $this->module->getExtraPath('Assets');
        if (!file_exists($path) && is_dir($public)) {
            symlink($public, $path);
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig()
    {
        $configPath = $this->module->getExtraPath('Config/config.php');

        $this->mergeConfigFrom(
            $configPath, "module.{$this->module->getLowerName()}"
        );

        $this->publishes([
            $configPath => config_path("module/{$this->module->getLowerName()}.php"),
        ]);
    }

    /**
     * Register views.
     */
    public function registerViews()
    {
        $viewPath = base_path("resources/views/modules/{$this->module->getLowerName()}");

        $sourcePath = $this->module->getExtraPath('Resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ]);

        $this->loadViewsFrom([$viewPath, $sourcePath], $this->module->getLowerName());
    }

    /**
     * Register translations.
     */
    public function registerTranslations()
    {
        $langPath = base_path("resources/lang/modules/{$this->module->getLowerName()}");

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->module->getLowerName());
        } else {
            $this->loadTranslationsFrom($this->module->getExtraPath('Resources/lang'), $this->module->getLowerName());
        }
    }
}