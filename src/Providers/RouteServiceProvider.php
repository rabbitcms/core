<?php

namespace RabbitCMS\Carrot\Providers;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to the controller routes in your routes file.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @param  \Illuminate\Routing\Router $router
     *
     * @return void
     */
    public function boot(Router $router)
{
    //

    parent::boot($router);
}

    /**
     * Define the routes for the application.
     *
     * @param  \Illuminate\Routing\Router $router
     *
     * @return void
     */
    public function map(Router $router)
{
    $router->group(['middleware' => ['api']], function (Router $router) {
        // Create an instance of JsonRpcServer
        /* @var ServerContract $jsonRpcServer */
        $jsonRpcServer = $this->app->make(ServerContract::class);
        // Set default controller namespace
        //$jsonRpcServer->setControllerNamespace($this->namespace);
        // Register middleware aliases configured for Laravel router
        $jsonRpcServer->registerMiddlewareAliases($router->getMiddleware());

        $router->post(
            '/jsonrpc',
            function (Request $request) use ($jsonRpcServer) {
                // Run json-rpc server with $request passed to middlewares as a handle() method argument
                return $jsonRpcServer->run($request);
            }
        );
        $router->group(
            ['namespace' => $this->namespace],
            function (Router $router) use ($jsonRpcServer) {
                require app_path('Http/routes.php');
            }
        );
        foreach ($this->app->make('modules')->getOrdered() as $module) {
            /* @var \Pingpong\Modules\Module $module */
            $autoload = $module->getComposerAttr('autoload');
            if (array_key_exists('psr-4', $autoload) && is_array($autoload['psr-4']) && count($autoload['psr-4']) > 0) {
                $namespace = key($autoload['psr-4']).'\Http\Controllers';
            } else {
                $namespace = '';
            }

            $jsonRpcServer->router()->group(
                ['namespace' => $namespace],
                function (\DKulyk\JsonRpc\Server\Router $router) use ($module, $jsonRpcServer) {
                    $path = $module->getExtraPath('Http/routes.php');
                    if (is_file($path)) {
                        require $path;
                    }
                }
            );
        }
    });
}
}