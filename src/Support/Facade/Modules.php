<?php

namespace RabbitCMS\Carrot\Support\Facade;


use Illuminate\Support\Facades\Facade;
use RabbitCMS\Carrot\Modules\Contracts\ModulesManager;
use RabbitCMS\Carrot\Modules\Repository;

/**
 * Class Modules Facade.
 * @method static Repository scan() Get all found modules.
 * @method static Repository enabled() Get enabled modules.
 * @method static enable(string $name) Enable module.
 * @method static disable(string $name) Disable module.
 */
class Modules extends Facade
{
    /**
     * @inheritdoc
     */
    protected static function getFacadeAccessor()
    {
        return ModulesManager::class;
    }
}