<?php

namespace N3m3s7s\Soft;

use Illuminate\Support\Facades\Facade;

class SoftFactoryFacade extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor()
    {
        return 'soft-factory';
    }
}