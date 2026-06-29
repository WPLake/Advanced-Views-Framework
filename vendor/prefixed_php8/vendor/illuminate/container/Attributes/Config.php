<?php

namespace Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Container\Attributes;

use Attribute;
use Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Contracts\Container\Container;
use Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Contracts\Container\ContextualAttribute;
#[\Attribute(Attribute::TARGET_PARAMETER)]
class Config implements ContextualAttribute
{
    /**
     * Create a new class instance.
     */
    public function __construct(public string $key, public mixed $default = null)
    {
    }
    /**
     * Resolve the configuration value.
     *
     * @param  self  $attribute
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return mixed
     */
    public static function resolve(self $attribute, Container $container)
    {
        return $container->make('config')->get($attribute->key, $attribute->default);
    }
}
