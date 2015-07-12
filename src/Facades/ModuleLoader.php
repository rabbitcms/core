<?php namespace RabbitCMS\Carrot\Facades;

use Illuminate\Support\Facades\Facade;

class ModuleLoader extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'module.loader';
    }
}