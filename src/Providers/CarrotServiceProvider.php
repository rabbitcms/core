<?php

namespace RabbitCMS\Carrot\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class CarrotServiceProvider extends IlluminateServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->registerConfig();
    }

    /**
     * Register config.
     */
    protected function registerConfig()
    {
        $configPath = dirname(__DIR__, 2) . '/config/config.php';

        $this->mergeConfigFrom($configPath, 'carrot');

        $this->publishes([$configPath => config_path('carrot.php')]);
    }

    public function boot()
    {
        $this->bootTrustedProxies();
    }

    protected function bootTrustedProxies()
    {
        Request::setTrustedProxies(
            $this->app->make('config')->get('carrot.trustedProxies', []),
            constant(Request::class .'::HEADER_X_FORWARDED_ALL')
        );
    }
}
