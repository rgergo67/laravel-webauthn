<?php

namespace LaravelWebauthn\Services;

use Base64Url\Base64Url;
use Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Facades\Http;
use LaravelWebauthn\Models\WebauthnKey;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRequestOptions;

class WebauthnClient
{

    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function auth($webauthnIdentifier, $publicKey, $data): bool
    {
        return (bool) $this->post('auth', [
            'webauthnIdentifier' => $webauthnIdentifier,
            'publicKey' => $publicKey,
            'data' => $data,
        ]);
    }

    public function create($webauthnIdentifier, $publicKey, $register, $keyName): bool
    {
        return (bool) $this->post('create', [
            'webauthnIdentifier' => $webauthnIdentifier,
            'publicKey' => $publicKey,
            'register' => $register,
            'key_name' => $keyName,
        ]);
    }

    public function getAllRegisteredKey(string $webauthnIdentifier)
    {
        $response = $this->get('get_all_registered_key', [
            'webauthn_identifier' => $webauthnIdentifier,
        ]);

        $webauthnKeys = collect();
        foreach ($response as $webauthnKey) {
            $webauthnKey = (array) $webauthnKey;
            $webauthnKey['credentialId'] = base64_decode($webauthnKey['credentialId']);
            $webauthnKey['credentialPublicKey'] = base64_decode($webauthnKey['credentialPublicKey']);
            $webauthnKey['trustPath'] = json_decode($webauthnKey['trustPath']);

            $webauthnKeys->push(WebauthnKey::make($webauthnKey));
        }

        return $webauthnKeys;
    }

    public function getWebauthnKey(string $credentialId): WebauthnKey
    {
        $response = (array) $this->get('get_webauthn_key', [
            'credential_id' => $credentialId,
        ]);

        $response['credentialId'] = base64_decode($response['credentialId']);
        $response['credentialPublicKey'] = base64_decode($response['credentialPublicKey']);
        $response['trustPath'] = json_decode($response['trustPath']);

        return WebauthnKey::make($response);
    }

    public function getPublicKey($webauthnIdentifier): PublicKeyCredentialRequestOptions
    {
        $response = $this->get('get_public_key', [
            'webauthn_identifier' => $webauthnIdentifier,
        ]);

        $allowCredentials = [];
        foreach ($response->allowCredentials as $allowCredential) {
            $allowCredentials[] = new PublicKeyCredentialDescriptor($allowCredential->type, Base64Url::decode($allowCredential->id), []);
        }

        return new PublicKeyCredentialRequestOptions(
            Base64Url::decode($response->challenge),
            $response->timeout,
            $response->rpId,
            $allowCredentials,
            $response->userVerification,
        );
    }

    public function hasKey($webauthnIdentifier): bool
    {
        return Cache::remember("webauthn_has_key_{$webauthnIdentifier}", 10, function () use ($webauthnIdentifier) {
            $response = $this->get('has_key', [
                'webauthn_identifier' => $webauthnIdentifier,
            ]);

            return (bool) $response->hasKey;
        });
    }

    public function storeWebauthnKey(WebauthnKey $webauthnKey)
    {
        $this->post('store_webauthn_key', [
            'webauthnKey' => $webauthnKey,
        ]);
    }

    protected function get($endPoint, $data)
    {
        return $this->apiRequest($endPoint, $data, 'GET');
    }

    protected function post($endPoint, $data)
    {
        return $this->apiRequest($endPoint, $data, 'POST');
    }

    protected function apiRequest($endPoint, $data, $method = 'POST')
    {
        $url =  route($this->config->get('webauthn.api_route_prefix') . $endPoint);

        if ($method === 'POST') {
            $response = Http::withoutVerifying()->acceptJson()->post($url, $data);
        }

        if ($method === 'GET') {
            $response = Http::withoutVerifying()->acceptJson()->get($url, $data);
        }

        if ($response->failed()) {
            abort($response->status(), json_decode($response->body())->message);
        }

        return json_decode($response->body());
    }
}
