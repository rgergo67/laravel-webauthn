<?php

namespace LaravelWebauthn\Http\Controllers;

use Base64Url\Base64Url;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use LaravelWebauthn\Facades\Webauthn;
use LaravelWebauthn\Facades\WebauthnClient;
use LaravelWebauthn\Models\WebauthnKey;
use LaravelWebauthn\Services\UserService;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRequestOptions;

class WebauthnRegistrationClientController extends Controller
{
    /**
     * PublicKey Creation session name.
     *
     * @var string
     */
    private const SESSION_PUBLICKEY_CREATION = 'webauthn.publicKeyCreation';


    /**
     * Return the register data to attempt a Webauthn registration.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function register()
    {
        $user = UserService::getUserByWebauthnIdentifier(request('webauthn_identifier'));

        $publicKey = Webauthn::getRegisterData($user);

        session()->put(self::SESSION_PUBLICKEY_CREATION, $publicKey);

        return $this->redirectViewRegister($user);
    }

    /**
     * Return the redirect destination on register.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    protected function redirectViewRegister(User $user)
    {
        return view(config('webauthn.register.view'), [
            'keyName' => request('key_name'),
            'name' => request('name'),
            'publicKey' => session(self::SESSION_PUBLICKEY_CREATION),
            'user' => $user,
        ]);
    }

    /**
     * Validate and create the Webauthn request.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function create()
    {
        $publicKey = session()->pull(self::SESSION_PUBLICKEY_CREATION);
        if (! $publicKey instanceof PublicKeyCredentialCreationOptions) {
            throw new ModelNotFoundException(trans('webauthn::errors.create_data_not_found'));
        }

        WebauthnClient::create(
            request('webauthn_identifier'),
            $publicKey,
            request('register'),
            request('key_name'),
        );

        return $this->redirectAfterSuccessRegister();
    }

    protected function redirectAfterSuccessRegister(WebauthnKey $webauthnKey)
    {
        return redirect()->route(config('webauthn.register.postSuccessRedirectRoute', '/'))->withSuccess(__('form.saved'));
    }
}
