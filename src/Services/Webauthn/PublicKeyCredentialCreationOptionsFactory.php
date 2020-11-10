<?php

namespace LaravelWebauthn\Services\Webauthn;

use Base64Url\Base64Url;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Facades\Request;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

final class PublicKeyCredentialCreationOptionsFactory extends AbstractOptionsFactory
{
    /**
     * Create a new PublicKeyCredentialCreationOptions object.
     *
     * @param User $user
     * @return PublicKeyCredentialCreationOptions
     */
    public function create(User $user): PublicKeyCredentialCreationOptions
    {
        $userEntity = new PublicKeyCredentialUserEntity(
            $user->email ?? '',
            $user->getWebauthnIdentifier(),
            $user->email ?? '',
            null
        );

        return new PublicKeyCredentialCreationOptions(
            $this->createRpEntity(),
            $userEntity,
            random_bytes($this->config->get('webauthn.challenge_length', 32)),
            $this->createCredentialParameters(),
            $this->config->get('webauthn.timeout', 60000),
            $this->repository->getRegisteredKeys($user),
            $this->createAuthenticatorSelectionCriteria(),
            $this->config->get('webauthn.attestation_conveyance') ?? PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            $this->createExtensions()
        );
    }

    public function createFromRequest($request, User $user): PublicKeyCredentialCreationOptions
    {
        $userEntity = new PublicKeyCredentialUserEntity(
            $user->email ?? '',
            $user->getWebauthnIdentifier(),
            $user->email ?? '',
            null
        );

        return new PublicKeyCredentialCreationOptions(
            new PublicKeyCredentialRpEntity(
                $request['rp']['name'],
                $request['rp']['id']
            ),
            $userEntity,
            Base64Url::decode($request['challenge']),
            $this->createCredentialParameters(),
            $request['timeout'],
            $this->repository->getRegisteredKeys($user),
            new AuthenticatorSelectionCriteria(
                $request['authenticatorSelection']['authenticatorAttachment'] ?? null,
                $request['authenticatorSelection']['requireResidentKey'] ?? null,
                $request['authenticatorSelection']['userVerification'] ?? null
            ),
            $request['attestation'],
            new AuthenticationExtensionsClientInputs()
        );
    }

    private function createAuthenticatorSelectionCriteria(): AuthenticatorSelectionCriteria
    {
        return new AuthenticatorSelectionCriteria(
            $this->config->get('webauthn.authenticator_selection_criteria.attachment_mode') ?? AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE,
            $this->config->get('webauthn.authenticator_selection_criteria.require_resident_key', false),
            $this->config->get('webauthn.authenticator_selection_criteria.user_verification') ?? AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED
        );
    }

    private function createRpEntity(): PublicKeyCredentialRpEntity
    {
        return new PublicKeyCredentialRpEntity(
            $this->config->get('app.name', 'Laravel'),
            $this->config->get('webauthn.hostname', Request::getHttpHost()),
            $this->config->get('webauthn.icon')
        );
    }

    /**
     * @return PublicKeyCredentialParameters[]
     */
    private function createCredentialParameters(): array
    {
        $callback = function ($algorithm): PublicKeyCredentialParameters {
            return new PublicKeyCredentialParameters(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                $algorithm
            );
        };

        return array_map($callback, $this->config->get('webauthn.public_key_credential_parameters') ?? [
            \Cose\Algorithms::COSE_ALGORITHM_ES256,
            \Cose\Algorithms::COSE_ALGORITHM_RS256,
        ]);
    }
}
