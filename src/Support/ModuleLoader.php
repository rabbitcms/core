<?php namespace RabbitCMS\Carrot\Support;

class ModuleLoader
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * @var ModuleProvider[]
     */
    protected $modules = [];

    /**
     * Create a new module loader instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    public function loadModules(array $modules)
    {
        /**
         * @var ModuleProvider $module
         */
        foreach ($modules as $name) {
            $path = base_path('modules/' . $name);
            $composer = json_decode(file_get_contents($path . '/composer.json'), true);
            $composer['module']['path'] = $path;
            $providerClass = $composer['module']['namespace'] . 'ModuleProvider';
            $this->modules[$name] = new $providerClass($this->app, $composer['module']);
        }
    }

    public function registerModules()
    {
        array_walk($this->modules, function (ModuleProvider $module) {
            $module->register();
        });
    }

    public function bootModules()
    {
        array_walk($this->modules, function (ModuleProvider $module) {
            $module->boot();
        });
    }

    public function getModules()
    {
        return $this->modules;
    }
}