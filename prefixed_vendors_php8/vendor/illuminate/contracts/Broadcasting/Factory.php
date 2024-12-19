<?php

namespace Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Contracts\Broadcasting;

interface Factory
{
    /**
     * Get a broadcaster implementation by name.
     *
     * @param  string|null  $name
     * @return \Illuminate\Contracts\Broadcasting\Broadcaster
     */
    public function connection($name = null);
}
