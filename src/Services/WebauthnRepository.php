<?php

namespace LaravelWebauthn\Services;

use Illuminate\Contracts\Auth\Authenticatable as User;
use LaravelWebauthn\Facades\WebauthnClient;
use LaravelWebauthn\Models\WebauthnKey;
use Webauthn\PublicKeyCredentialSource;

abstract class WebauthnRepository
{
    /**
     * Create a new key.
     *
     * @param User $user
     * @param string $keyName
     * @param PublicKeyCredentialSource $publicKeyCredentialSource
     * @return WebauthnKey
     */
    public function create(User $user, string $keyName, PublicKeyCredentialSource $publicKeyCredentialSource)
    {
        $webauthnKey = WebauthnKey::make([
            'user_id' => $user->getWebauthnIdentifier(),
            'name' => $keyName,
        ]);
        $webauthnKey->publicKeyCredentialSource = $publicKeyCredentialSource;
        $webauthnKey->save();

        return $webauthnKey;
    }

    /**
     * Test if the user has one webauthn key set or more.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return bool
     */
    public function hasKey(User $user): bool
    {
        return (bool) WebauthnClient::hasKey($user->getWebauthnIdentifier());
    }
}
