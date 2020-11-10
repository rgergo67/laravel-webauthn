<?php

namespace LaravelWebauthn\Traits;

trait WebauthnTrait
{

    public function getWebauthnIdentifier()
    {
        return $this->webauthnIdentifier ?? $this->getAuthIdentifier();
    }

    public function getWebauthnIdentifierName()
    {
        return $this->webauthnIdentifierName ?? $this->getAuthIdentifierName();
    }

    public function findByWebauthnIdentifier($id)
    {
        return $this->where($this->getWebauthnIdentifierName(), $id)
            ->first();
    }
}
