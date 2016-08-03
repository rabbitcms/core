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

    public function __construct(\Illuminate\Contracts\Foundation\Application $app)
    {
        parent::__construct($app);
        $this->moduleManager = $this->app->make('modules');
        $this->module = $this->moduleManager->get($this->name());
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
        $path = $this->moduleManager->getAssetsPath() . '/' . $this->module->getLowerName();
        $public = $this->module->getExtraPath('Assets');
        if (!file_exists($path) && is_dir($public)) {
            symlink($this->getRelativePath($path, $public), $path);
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