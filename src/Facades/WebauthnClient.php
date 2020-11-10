<?php

namespace LaravelWebauthn\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \LaravelWebauthn\WebauthnClient
 */
class WebauthnClient extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \LaravelWebauthn\Services\WebauthnClient::class;
    }
}
