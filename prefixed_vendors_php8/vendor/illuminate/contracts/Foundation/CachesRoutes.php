<?php

namespace Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Contracts\Foundation;

interface CachesRoutes
{
    /**
     * Determine if the application routes are cached.
     *
     * @return bool
     */
    public function routesAreCached();
    /**
     * Get the path to the routes cache file.
     *
     * @return string
     */
    public function getCachedRoutesPath();
}
