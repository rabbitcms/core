<?php
namespace RabbitCMS\Carrot\Modules;

use FilesystemIterator;
use Illuminate\Contracts\Container\Container;
use RabbitCMS\Carrot\Modules\Contracts\ModulesManager;
use RecursiveDirectoryIterator;
use RuntimeException;
use SplFileInfo;

class Manager implements ModulesManager
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var Repository
     */
    protected $modules;

    /**
     * Manager constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->restore();
    }

    /**
     * @inheritdoc
     */
    public function restore()
    {
        if (!is_file($file = base_path('bootstrap/cache/modules.php'))) {
            return false;
        }
        $modules = new Repository();

        foreach (require $file as $module) {
            $modules->add(new Module($module));
        }
        $this->modules = $modules;
        return true;
    }

    /**
     * @inheritdoc
     */
    public function scan($store = true)
    {
        $modules = new Repository();
        foreach ((array)$this->config('modules.paths', []) as $path) {
            foreach (new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS) as $file) {

                /* @var SplFileInfo $file */
                if (!$file->isDir()) {
                    continue;
                }
                if (!is_file($composerFile = $file->getPathname() . '/composer.json')) {
                    continue;
                }
                $composer = json_decode(file_get_contents($composerFile), true);
                if (empty($composer['extra']['module']) || !is_array($composer['extra']['module'])) {
                    continue;
                }
                $module = $composer['extra']['module'];
                if (empty($module['namespace'])) {
                    if (isset($composer['autoload']['psr-4']) && is_array($composer['autoload']['psr-4']) && count($composer['autoload']['psr-4']) > 0) {
                        $module['namespace'] = rtrim($composer['autoload']['psr-4'], '\\');
                    } else {
                        throw new RuntimeException('Module namespace must be set.');
                    }
                }
                $module['path'] = $file->getPathname();
                $module = new Module($module);
                if ($this->modules->has($module->getName())) {
                    $module->setEnabled($this->modules->get($module->getName())->isEnabled());
                }
                $modules->add($module);
            }
        }

        $this->modules = $modules;
        if ($store) {
            $this->store();
        }

        return $this->modules;
    }

    /**
     * Get the specified configuration value.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function config($key, $default = null)
    {
        return $this->container->make('config')->get('carrot.' . $key, $default);
    }

    /**
     * Store modules for fast load.
     */
    public function store()
    {
        file_put_contents(base_path('bootstrap/cache/modules.php'), "<?php return " . var_export($this->all()->toArray(), true) . ";\n");
    }

    /**
     * Get all modules.
     *
     * @return Repository
     */
    public function all()
    {
        if ($this->modules === null && !$this->restore()) {
            $this->modules = new Repository();
            $this->scan();
        }

        return $this->modules;
    }

    /**
     * @inheritdoc
     */
    public function enabled()
    {
        return $this->all()->filter(
            function (Module $module) {
                return $module->isEnabled();
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function disable($name)
    {
        $module = $this->all()->get($name);
        if ($module->isSystem()) {
            throw new RuntimeException('Don\'t disable system module.');
        }
        $module->setEnabled(false);
        $this->store();
    }

    /**
     * @inheritdoc
     */
    public function enable($name)
    {
        $this->all()->get($name)->setEnabled(true);
        $this->store();
    }
}