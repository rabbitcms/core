<?php

namespace RabbitCMS\Carrot\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use RabbitCMS\Carrot\Modules\Contracts\ModulesManager;
use RabbitCMS\Carrot\Modules\Module;

abstract class ModuleProvider extends IlluminateServiceProvider
{
    /**
     * @var Module
     */
    protected $module;

    /**
     * @var ModulesManager
     */
    protected $modulesManager;

    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->modulesManager = $this->app->make(ModulesManager::class);
        $this->module = $this->modulesManager->get($this->name());
    }

    /**
     * Fetch module name
     *
     * @return string
     */
    abstract protected function name();

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->registerConfig();
        $this->registerTranslations();
        $this->registerViews();
        $path = app_path('module' . '/' . $this->module->getName());
        $public = $this->module->getPath('Assets');
        if (!file_exists($path) && is_dir($public)) {
            $link = defined('PHP_WINDOWS_VERSION_MAJOR') ? $public : $this->getRelativePath($path, $public);
            symlink($link, $path);
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig()
    {
        $configPath = $this->module->getPath('Config/config.php');

        $this->mergeConfigFrom($configPath, "module.{$this->module->getName()}");

        $this->publishes([$configPath => config_path("module/{$this->module->getName()}.php")]);
    }

    /**
     * Register translations.
     */
    public function registerTranslations()
    {
        $langPath = base_path("resources/lang/modules/{$this->module->getName()}");

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->module->getName());
        } else {
            $this->loadTranslationsFrom($this->module->getPath('Resources/lang'), $this->module->getName());
        }
    }

    /**
     * Register views.
     */
    public function registerViews()
    {
        $viewPath = base_path("resources/views/modules/{$this->module->getName()}");

        $sourcePath = $this->module->getPath('Resources/views');

        $this->publishes([$sourcePath => $viewPath]);

        $this->loadViewsFrom([$viewPath, $sourcePath], $this->module->getName());
    }

    private function getRelativePath($from, $to)
    {
        // some compatibility fixes for Windows paths
        $from = is_dir($from) ? rtrim($from, '\/') . '/' : $from;
        $to = is_dir($to) ? rtrim($to, '\/') . '/' : $to;
        $from = str_replace('\\', '/', $from);
        $to = str_replace('\\', '/', $to);

        $from = explode('/', $from);
        $to = explode('/', $to);
        $relPath = $to;

        foreach ($from as $depth => $dir) {
            // find first non-matching dir
            if ($dir === $to[$depth]) {
                // ignore this directory
                array_shift($relPath);
            } else {
                // get number of remaining dirs to $from
                $remaining = count($from) - $depth;
                if ($remaining > 1) {
                    // add traversals up to first matching dir
                    $padLength = (count($relPath) + $remaining - 1) * -1;
                    $relPath = array_pad($relPath, $padLength, '..');
                    break;
                } else {
                    $relPath[0] = './' . $relPath[0];
                }
            }
        }

        return implode('/', $relPath);
    }
}
