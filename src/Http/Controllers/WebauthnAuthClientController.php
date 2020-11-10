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

class WebauthnAuthClientController extends Controller
{
    /**
     * PublicKey Request session name.
     *
     * @var string
     */
    private const SESSION_PUBLICKEY_REQUEST = 'webauthn.publicKeyRequest';

    /**
     * Show the permanent login Webauthn request after a login authentication.
     *
     * @param string $webauthnIdentifier User's webauthn identifier like asd@uni-pannon.hu KCA
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function loginPermanent(string $webauthnIdentifier = null)
    {
        return $this->login($webauthnIdentifier);
    }

    /**
     * Show the 'once' login Webauthn request after a login authentication.
     *
     * @param string $webauthnIdentifier User's webauthn identifier like asd@uni-pannon.hu KCA
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function loginOnce(string $webauthnIdentifier = null)
    {
        return $this->login($webauthnIdentifier, true);
    }

    /**
     * Show the login Webauthn request after a login authentication.
     *
     * @param string $webauthnIdentifier User's webauthn identifier like asd@uni-pannon.hu KCA
     * @param bool $once Is this login permanent or a one time
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    protected function login(string $webauthnIdentifier = null, $once = false)
    {
        $user = UserService::getUserByWebauthnIdentifier($webauthnIdentifier);

        if (is_null($user)) {
            $user = auth()->user();
        }

        $publicKey = WebauthnClient::getPublicKey($user->getWebauthnIdentifier());

        session()->put(self::SESSION_PUBLICKEY_REQUEST, $publicKey);

        return $this->redirectViewAuth($user, $once);
    }

    /**
     * Return the redirect destination on login.
     *
     * @param bool $once Is this login permanent or a one time
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    protected function redirectViewAuth(User $user, $once = false)
    {
        return view(config('webauthn.authenticate.view'), [
                'user' => $user,
                'publicKey' => session(self::SESSION_PUBLICKEY_REQUEST),
                'once' => $once,
            ]);
    }

    /**
     * Authenticate a webauthn request.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function auth()
    {
        $publicKey = session()->pull(self::SESSION_PUBLICKEY_REQUEST);
        if (! $publicKey instanceof PublicKeyCredentialRequestOptions) {
            throw new ModelNotFoundException(trans('webauthn::errors.auth_data_not_found'));
        }

        $result = WebauthnClient::auth(request('webauthn_identifier'), $publicKey, request('data'));

        if ($result) {
            Webauthn::forceAuthenticate();
        }

        return $this->redirectAfterSuccessAuth($result);
    }

    /**
     * Return the redirect destination after a successfull auth.
     *
     * @param bool $result
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    protected function redirectAfterSuccessAuth(bool $result)
    {
        if (request()->hasSession() && session()->has('url.intended')) {
            return Redirect::intended();
        }

        return redirect('/')->withSuccess('Sikeres authentikáció');
    }
}
