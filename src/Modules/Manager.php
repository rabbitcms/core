<?php
namespace RabbitCMS\Carrot\Modules;

use FilesystemIterator;
use Illuminate\Contracts\Foundation\Application;
use RabbitCMS\Carrot\Modules\Contracts\ModulesManager;
use RecursiveDirectoryIterator;
use RuntimeException;
use SplFileInfo;

class Manager implements ModulesManager
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Repository
     */
    protected $modules;

    /**
     * Manager constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
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
            if (!is_dir($path)) {
                continue;
            }
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
                    //$module->setEnabled($this->modules->get($module->getName())->isEnabled());
                }
                $path = public_path($this->getAssetsPath() . '/' . $module->getName());
                $public = $module->getPath('Assets');

                if (!file_exists($path) && is_dir($public)) {
                    $link = defined('PHP_WINDOWS_VERSION_MAJOR') ? $public : $this->getRelativePath($path, $public);
                    symlink($link, $path);
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
        return $this->app->make('config')->get('carrot.' . $key, $default);
    }

    /**
     * Get modules assets path.
     *
     * @return string
     */
    public function getAssetsPath()
    {
        return $this->config('assets', 'modules');
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

    /**
     * Register module providers.
     */
    public function register()
    {
        $this->enabled()->each(
            function (Module $module) {
                array_map(
                    function ($provider) {
                        $this->app->register($provider);
                    },
                    $module->getProviders()
                );
            }
        );
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
    public function has($name)
    {
        return $this->all()->has($name);
    }

    /**
     * @inheritdoc
     */
    public function get($name)
    {
        return $this->all()->get($name);
    }

    /**
     * Generate a URL to an application asset.
     *
     * @param  string    $path
     * @param  bool|null $secure
     *
     * @return string
     */
    public function asset($path, $secure = null)
    {
        list($name, $url) = explode(':', $path);

        return $this->app['url']->asset($this->getAssetsPath() . "/{$name}/" . $url, $secure);
    }
}