<?php

namespace LaravelWebauthn\Http\Controllers\Api;

use App\Facades\SamlLoginService;
use Base64Url\Base64Url;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use LaravelWebauthn\Facades\Webauthn;
use LaravelWebauthn\Models\WebauthnKey;
use LaravelWebauthn\Services\UserService;
use LaravelWebauthn\Services\Webauthn\PublicKeyCredentialCreationOptionsFactory;
use Throwable;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRequestOptions;

class WebauthnApiController
{

    public function auth()
    {
        $user = UserService::getUserByWebauthnIdentifier(request('webauthnIdentifier'));

        $publicKey = request('publicKey');

        $allowCredentials = [];
        foreach ($publicKey['allowCredentials'] as $allowCredential) {
            $allowCredentials[] = new PublicKeyCredentialDescriptor($allowCredential['type'], Base64Url::decode($allowCredential['id']), []);
        }

        $newPublicKey = new PublicKeyCredentialRequestOptions(
            Base64Url::decode($publicKey['challenge']),
            $publicKey['timeout'],
            $publicKey['rpId'],
            $allowCredentials,
            $publicKey['userVerification'],
        );

        return Webauthn::doAuthenticate(
            $user,
            $newPublicKey,
            request('data')
        );
    }

    public function create()
    {
        $user = UserService::getUserByWebauthnIdentifier(request('webauthnIdentifier'));

        $publicKey = app()->make(PublicKeyCredentialCreationOptionsFactory::class)
            ->createFromRequest(request('publicKey'), $user);

        return Webauthn::doRegister(
            $user,
            $publicKey,
            request('register'),
            request('key_name')
        );
    }

    /**
     * Display systems that are allowed for idp login.
     *
     * @return Response
     */
    public function getPublicKey()
    {
        $user =  UserService::getUserByWebauthnIdentifier(request('webauthn_identifier'));

        $publicKey = Webauthn::getAuthenticateData($user);

        return $publicKey;
    }

    public function getAllRegisteredKey()
    {
        return WebauthnKey::where('user_id', request('webauthn_identifier'))->get();
    }

    public function getWebauthnKey()
    {
        try {
            return WebauthnKey::where('credentialId', request('credential_id'))->firstOrFail();
        } catch (ModelNotFoundException $e) {
            abort(404, request('credential_id') . ' azonosítójú kulcs nem található.');
        } catch (Throwable $e) {
            abort(500, 'Rendszer hiba.');
        }
    }

    public function hasKey()
    {
        $hasKey = WebauthnKey::where('user_id', request('webauthn_identifier'))->count() > 0;

        return ['hasKey' => $hasKey];
    }

    public function storeWebauthnKey()
    {
        $requestData = request('webauthnKey');
        $webauthnKey = WebauthnKey::where('credentialId', $requestData['credentialId'])->first();

        $requestData['trustPath'] = json_decode($requestData['trustPath']);
        $requestData['credentialId'] = base64_decode($requestData['credentialId']);
        $requestData['credentialPublicKey'] = base64_decode($requestData['credentialPublicKey']);
        $newWebauthnKey = WebauthnKey::make($requestData);
        $webauthnKey->publicKeyCredentialSource = $newWebauthnKey->publicKeyCredentialSource;
        $webauthnKey->save();

        return $webauthnKey;
    }
}
