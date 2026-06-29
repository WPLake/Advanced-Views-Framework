<?php

namespace Org\Wplake\Advanced_Views\Optional_Vendors\Jenssegers\Blade;

use Closure;
use Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Container\Container as BaseContainer;
class Container extends BaseContainer
{
    protected array $terminatingCallbacks = [];
    public function terminating(Closure $callback)
    {
        $this->terminatingCallbacks[] = $callback;
        return $this;
    }
    public function terminate()
    {
        foreach ($this->terminatingCallbacks as $terminatingCallback) {
            $terminatingCallback();
        }
    }
}
