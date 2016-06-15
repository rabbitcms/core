<?php

namespace RabbitCMS\Carrot\Providers;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\ServiceProvider;
use RabbitCMS\Carrot\Contracts\HasAccessEntity;
use RabbitCMS\Carrot\Repository\BackendAcl;

class AclToGateProvider extends ServiceProvider
{
    /**
     * @inheritdoc
     */
    public function register()
    {
    }

    /**
     * Define acl as gate permissions.
     *
     * @param \Illuminate\Contracts\Auth\Access\Gate  $gate
     * @param \RabbitCMS\Carrot\Repository\BackendAcl $acl
     */
    public function boot(Gate $gate, BackendAcl $acl)
    {
        \App::booted(
            function () use ($gate, $acl) {
                foreach ($acl->getAll() as $acl => $label) {
                    $gate->define(
                        $acl,
                        function (HasAccessEntity $user) use ($acl) {
                            return $user->hasAccess($acl);
                        }
                    );
                }
            }
        );
    }
}