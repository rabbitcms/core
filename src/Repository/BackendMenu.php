<?php
namespace RabbitCMS\Carrot\Repository;

use Illuminate\Contracts\Container\Container;
use RabbitCMS\Carrot\Contracts\HasAccessEntity;

class BackendMenu
{
    /**
     * Menu resolvers.
     *
     * @var (string|callable)[]
     */
    protected $menuResolvers = [];

    /**
     * Items resolvers.
     *
     * @var (string|callable)[]
     */
    protected $itemResolvers = [];

    /**
     * Menu definitions.
     *
     * @var array|null
     */
    protected $menu = null;

    /**
     * @var Container
     */
    protected $container;

    /**
     * Active path.
     *
     * @var string
     */
    protected $active;

    /**
     * @var bool
     */
    protected $changed = false;

    /**
     * BackendMenuRepository constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Add menu resolver.
     *
     * @param callable|string $callback
     */
    public function addMenuResolver($callback)
    {
        $this->menuResolvers[] = $callback;
    }

    /**
     * Add items resolver.
     *
     * @param callable|string $callback
     */
    public function addItemsResolver($callback)
    {
        $this->itemResolvers[] = $callback;
    }

    /**
     * Define backend menu.
     *
     * @param string      $name
     * @param string      $caption
     * @param string|null $url
     * @param array|null  $permissions
     * @param string|null $icon
     * @param int         $position
     */
    public function addMenu($name, $caption, $url = null, $icon = null, array $permissions = null, $position = 0)
    {
        $this->addItem(null, $name, $caption, $url, $icon, $permissions, $position);
    }

    /**
     * Define backend item.
     *
     * @param string|null $parent
     * @param string      $name
     * @param string      $caption
     * @param string      $url
     * @param array|null  $permissions
     * @param string|null $icon
     * @param int         $position
     */
    public function addItem($parent, $name, $caption, $url, $icon = null, array $permissions = null, $position = 0)
    {
        $this->changed = true;
        $menu = ['items' => &$this->menu];
        if ($parent === null) {
            $path = $name;
        } else {
            $parents = explode('.', $parent);

            while (count($parents) > 0 && array_key_exists($parents[0], $menu['items'])) {
                $menu = &$menu['items'][$parents[0]];
                array_shift($parents);
            }
            if (count($parents) > 0) {
                throw new \RuntimeException(sprintf('Item with name "%s" not found.', $parent));
            }
            $path = $menu['path'] . '.' . $name;
        }

        if (array_key_exists($name, $menu['items'])) {
            throw  new \RuntimeException(sprintf('Item with name "%s" in menu "%s" already exist.', $name, $parent));
        }

        $items = [];

        $menu['items'][$name] = compact('caption', 'url', 'permissions', 'icon', 'name', 'position', 'path', 'items');
    }

    /**
     * Get allowed menu.
     *
     * @param string $guard [optional]
     *
     * @return array
     */
    public function getAccessMenu($guard = null)
    {
        $user = \Auth::guard($guard)->user();
        if (!($user instanceof HasAccessEntity)) {
            return [];
        }

        return $this->accessFilter($user, $this->getMenu());
    }

    /**
     * Check items permissions.
     *
     * @param \RabbitCMS\Carrot\Contracts\HasAccessEntity $user
     * @param array                                       $items
     *
     * @return array
     */
    protected function accessFilter(HasAccessEntity $user, array $items)
    {
        $filteredItems = array_filter(
            $items,
            function ($item) use ($user) {
                return $item['permissions'] === null || $user->hasAccess($item['permissions'], false);
            }
        );

        array_walk(
            $filteredItems,
            function (&$item) use ($user) {
                if (count($item['items']) > 0) {
                    $item['items'] = $this->accessFilter($user, $item['items']);
                }
            }
        );

        //cleanup empty menus
        return array_filter(
            $filteredItems,
            function ($item) use ($user) {
                return $item['url'] !== null || count($item['items']) > 0;
            }
        );
    }

    /**
     * Get menu definitions.
     *
     * @return array
     */
    public function getMenu()
    {
        if ($this->menu === null) {
            $this->menu = [];
            array_walk(
                $this->menuResolvers,
                function ($callback) {
                    $this->container->call($callback, [$this]);
                }
            );
            array_walk(
                $this->itemResolvers,
                function ($callback) {
                    $this->container->call($callback, [$this]);
                }
            );
        }

        if ($this->changed) {
            $this->sort($this->menu);
            $this->changed = false;
        }

        return $this->menu;
    }

    /**
     * Sort menu items.
     *
     * @param array $items
     *
     * @return void
     */
    protected function sort(array &$items)
    {
        uasort(
            $items,
            function (array $a, array $b) {
                return $a['position'] > $b['position'];
            }
        );

        array_walk(
            $items,
            function (&$item) {
                $this->sort($item['items']);
            }
        );
    }

    /**
     * Set active path.
     *
     * @param $path
     */
    public function setActive($path)
    {
        $this->active = $path;
    }

    /**
     * Check active item.
     *
     * @param array $item
     *
     * @return bool
     */
    public function isActive(array $item)
    {
        return preg_match('/^' . preg_quote($item['path']) . '/', $this->active) != 0;
    }

    /**
     * Get active items.
     *
     * @return array
     */
    public function getActiveItems()
    {
        $path = explode('.', $this->active);
        $items = $this->getMenu();
        $result = [];
        while (count($path) > 0 && array_key_exists($path[0], $items)) {
            $item = $result[] = $items[array_shift($path)];
            $items = $item['items'];
        }

        return $result;
    }
}