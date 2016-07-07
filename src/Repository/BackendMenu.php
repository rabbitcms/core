<?php
namespace RabbitCMS\Carrot\Repository;

use RabbitCMS\Backend\Support\Backend;

/**
 * Class BackendMenu.
 * @deprecated
 */
class BackendMenu
{
    /**
     * @var Backend
     */
    protected $backend;

    /**
     * BackendMenuRepository constructor.
     *
     * @param Backend $backend
     */
    public function __construct(Backend $backend)
    {
        $this->backend = $backend;
    }

    /**
     * Add menu resolver.
     *
     * @param callable|string $callback
     */
    public function addMenuResolver($callback)
    {
        $this->backend->addMenuResolver($callback, Backend::MENU_PRIORITY_MENU);
    }

    /**
     * Add items resolver.
     *
     * @param callable|string $callback
     */
    public function addItemsResolver($callback)
    {
        $this->backend->addMenuResolver($callback, Backend::MENU_PRIORITY_ITEMS);
    }

    /**
     * Define backend menu.
     *
     * @param string $name
     * @param string $caption
     * @param string|null $url
     * @param array|null $permissions
     * @param string|null $icon
     * @param int $position
     */
    public function addMenu($name, $caption, $url = null, $icon = null, array $permissions = null, $position = 0)
    {
        $this->backend->addMenu(null, $name, $caption, $url, $icon, $permissions, $position);
    }

    /**
     * Define backend item.
     *
     * @param string|null $parent
     * @param string $name
     * @param string $caption
     * @param string $url
     * @param array|null $permissions
     * @param string|null $icon
     * @param int $position
     */
    public function addItem($parent, $name, $caption, $url, $icon = null, array $permissions = null, $position = 0)
    {
        $this->backend->addMenu($parent, $name, $caption, $url, $icon, $permissions, $position);
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
        return $this->backend->getAccessMenu($guard);
    }

    /**
     * Set active path.
     *
     * @param $path
     */
    public function setActive($path)
    {
        $this->backend->setActiveMenu($path);
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
        return $this->backend->isActiveMenu($item);
    }

    /**
     * Get active items.
     *
     * @return array
     */
    public function getActiveItems()
    {
        return $this->backend->getActiveMenuItems();
    }

    /**
     * Get menu definitions.
     *
     * @return array
     */
    public function getMenu()
    {
        return $this->backend->getMenu();
    }
}