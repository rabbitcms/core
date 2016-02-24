<?php namespace RabbitCMS\Carrot\Providers;

use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use RabbitCMS\Carrot\Support\Layout;

class ServiceProvider extends BaseServiceProvider
{
    /**
     *
     */
    public function register()
    {
        $this->app->singleton('rabbitcms.layout', function () {
            return new Layout();
        });

        $this->registerUrlGenerator();
    }

    /**
     * Register the URL generator service.
     *
     * @return void
     */
    protected function registerUrlGenerator()
    {
        UrlGenerator::macro('assetModule', function ($module, $asset) {
            return $this->asset('modules/' . $module . '/assets/' . $asset);
        });
    }
}