<?php

namespace Lauthz\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Casbin\Enforcer
 */
class Enforcer extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'enforcer';
    }
}
