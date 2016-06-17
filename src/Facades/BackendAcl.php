<?php namespace RabbitCMS\Carrot\Facades;

use Illuminate\Support\Facades\Facade;
use RabbitCMS\Carrot\Repository\BackendAcl as BackendACLRepository;

class BackendAcl extends Facade
{
    /**
     * @inheritdoc
     */
    protected static function getFacadeAccessor()
    {
        return BackendACLRepository::class;
    }
}