<?php namespace RabbitCMS\Carrot\Facades;

use Illuminate\Support\Facades\Facade;
use RabbitCMS\Carrot\Repository\BackendMenu as BackendMenuRepository;

/**
 * Class BackendMenu.
 * @deprecated
 */
class BackendMenu extends Facade
{
    /**
     * @inheritdoc
     */
    protected static function getFacadeAccessor()
    {
        return BackendMenuRepository::class;
    }
}