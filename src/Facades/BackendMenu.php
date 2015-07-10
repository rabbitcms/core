<?php namespace Carrot\Facades;

use Illuminate\Support\Facades\Facade;

class BackendMenu extends Facade
{
    protected static function getFacadeAccessor(){
        return 'backend.menu';
    }
}