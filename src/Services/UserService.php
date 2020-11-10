<?php

namespace LaravelWebauthn\Services;

use Illuminate\Contracts\Auth\Authenticatable as User;
use LaravelWebauthn\Models\WebauthnKey;
use Webauthn\PublicKeyCredentialSource;

class UserService
{
    public static function getUserByWebauthnIdentifier($webauthnIdentifier)
    {
        if (empty($webauthnIdentifier)) {
            return null;
        }

        foreach(config('auth.providers') as $provider){
            $model = new $provider['model'];
            $user = $model->findByWebauthnIdentifier($webauthnIdentifier);
            if (! empty($user))
            {
                return $user;
            }
        }

        return null;
    }
}
