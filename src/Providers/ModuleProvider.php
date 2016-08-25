<?php

namespace RabbitCMS\Carrot\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use RabbitCMS\Carrot\Modules\Contracts\ModulesManager;
use RabbitCMS\Carrot\Modules\Module;
use RabbitCMS\Carrot\Modules\Provider;

/**
 * Class ModuleProvider.
 * @deprecated Use \RabbitCMS\Carrot\Modules\Provider
 */
abstract class ModuleProvider extends Provider
{
}
