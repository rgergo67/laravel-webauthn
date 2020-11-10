<?php

namespace LaravelWebauthn\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Facades\Redirect;
use LaravelWebauthn\Facades\Webauthn;

class WebauthnMiddleware
{
    /**
     * The config repository instance.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * Create a Webauthn.
     *
     * @param \Illuminate\Contracts\Config\Repository $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $type = 'permanent', $onlyWithKey = false)
    {
        if (config('webauthn.enable') === false) {
            return $next($request);
        }

        if (session()->has('guard')) {
            auth()->setDefaultDriver(session('guard'));
        }

        if (! Webauthn::hasActiveSession($type)) {

            // Webauthn routes should be behind auth middleware, but if not, check if user is logged in
            abort_if(auth()->guest(), 401, trans('webauthn::errors.user_unauthenticated'));

            if (Webauthn::hasKey(auth()->user())) {
                $route = route("webauthn.login_{$type}");
                return ($request->hasSession() && $request->session()->has('url.intended'))
                    ? Redirect::to($route)
                    : Redirect::guest($route);
            } else if ($onlyWithKey === 'onlywithkey') {
                abort(401, trans('webauthn::errors.key_needed'));
            }
        }

        return $next($request);
    }
}
