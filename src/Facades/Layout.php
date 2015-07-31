<?php namespace RabbitCMS\Carrot\Facades;

use Illuminate\Support\Facades\Facade;

class Layout extends Facade
{
    protected static function getFacadeAccessor(){
        return 'rabbitcms.layout';
    }
}