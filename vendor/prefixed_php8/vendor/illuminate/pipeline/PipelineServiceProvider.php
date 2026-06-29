<?php

namespace Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Pipeline;

use Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Contracts\Pipeline\Hub as PipelineHubContract;
use Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Contracts\Support\DeferrableProvider;
use Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Support\ServiceProvider;
class PipelineServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(PipelineHubContract::class, Hub::class);
        $this->app->bind('pipeline', fn($app) => new Pipeline($app));
    }
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [PipelineHubContract::class, 'pipeline'];
    }
}
