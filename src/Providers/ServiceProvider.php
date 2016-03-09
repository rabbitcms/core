<?php namespace RabbitCMS\Carrot\Providers;

use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use RabbitCMS\Carrot\Support\Bower;
use RabbitCMS\Carrot\Support\Layout;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * {@inheritdoc}
     */
    protected $defer = true;

    /**
     *
     */
    public function register()
    {
        $this->app->singleton('rabbitcms.layout', function () {
            return new Layout();
        });

        $this->registerBower();
    }

    /**
     * Register Bower.
     *
     * @return void
     */
    protected function registerBower()
    {
        $this->app->singleton('rabbitcms.bower', function () {
            return new Bower();
        });
    }

    public function provides()
    {
        return [
            'rabbitcms.bower',
            'rabbitcms.layout',
        ];
    }
}