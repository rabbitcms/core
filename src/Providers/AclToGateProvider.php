<?php

namespace RabbitCMS\Carrot\Providers;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use RabbitCMS\Carrot\Contracts\HasAccessEntity;
use RabbitCMS\Carrot\Repository\BackendAcl;

class AclToGateProvider extends \RabbitCMS\Backend\Providers\AclToGateProvider
{
}