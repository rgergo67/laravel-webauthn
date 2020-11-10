<?php

namespace LaravelWebauthn;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use LaravelWebauthn\Http\Controllers\Api\WebauthnApiController;
use LaravelWebauthn\Http\Controllers\WebauthnAuthClientController;
use LaravelWebauthn\Http\Controllers\WebauthnRegistrationClientController;

class WebauthnServiceProvider extends ServiceProvider
{
    /**
     * Name of the middleware group.
     *
     * @var string
     */
    private const MIDDLEWARE_GROUP = 'laravel-webauthn';

    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        Route::middlewareGroup(self::MIDDLEWARE_GROUP, config('webauthn.middleware', []));

        $this->registerRoutes();
        $this->registerPublishing();
        $this->registerResources();
    }

    /**
     * Register the package routes.
     *
     * @psalm-suppress InvalidArgument
     *
     * @return void
     */
    private function registerRoutes()
    {
        Route::group($this->routeConfiguration(), function (): void {
            Route::get('login-permanent/{webauthnIdentifier?}', [WebauthnAuthClientController::class, 'loginPermanent'])->name('webauthn.login_permanent');
            Route::get('login-once/{webauthnIdentifier?}', [WebauthnAuthClientController::class, 'loginOnce'])->name('webauthn.login_once');
            Route::post('auth', [WebauthnAuthClientController::class, 'auth'])->name('webauthn.auth');
            Route::post('register', [WebauthnRegistrationClientController::class, 'register'])->name('webauthn.register');
            Route::post('create', [WebauthnRegistrationClientController::class, 'create'])->name('webauthn.create');
        });

        Route::prefix('api')
            ->middleware('api')
            ->group(function (): void {
                Route::get('webauthn/get-all-registered-key', [WebauthnApiController::class, 'getAllRegisteredKey']);
                Route::get('webauthn/get-public-key', [WebauthnApiController::class, 'getPublicKey']);
                Route::get('webauthn/get-webauthn-key', [WebauthnApiController::class, 'getWebauthnKey']);
                Route::get('webauthn/has-key', [WebauthnApiController::class, 'hasKey']);
                Route::post('webauthn/auth', [WebauthnApiController::class, 'auth']);
                Route::post('webauthn/create', [WebauthnApiController::class, 'create']);
                Route::post('webauthn/store-webauthn-key', [WebauthnApiController::class, 'storeWebauthnKey']);
            });

        Route::domain(config('webauthn.api_base', null))
            ->name(config('webauthn.api_route_name_prefix', 'webauthn_api.'))
            ->group(function (): void {
                Route::get('webauthn/get-all-registered-key')->name('get_all_registered_key');
                Route::get('webauthn/get-public-key')->name('get_public_key');
                Route::get('webauthn/get-webauthn-key')->name('get_webauthn_key');
                Route::get('webauthn/has-key')->name('has_key');
                Route::post('webauthn/store-webauthn-key')->name('store_webauthn_key');
                Route::post('webauthn/auth')->name('auth');
                Route::post('webauthn/create')->name('create');
            });
    }

    /**
     * Get the route group configuration array.
     *
     * @return array
     */
    private function routeConfiguration()
    {
        return [
            'middleware' => self::MIDDLEWARE_GROUP,
            'domain' => config('webauthn.domain', null),
            'prefix' => config('webauthn.url_prefix', 'webauthn'),
        ];
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    private function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/webauthn.php' => config_path('webauthn.php'),
            ], 'webauthn-config');

            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'webauthn-migrations');

            $this->publishes([
                __DIR__.'/../resources/js' => public_path('vendor/webauthn'),
            ], 'webauthn-assets');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/webauthn'),
            ], 'webauthn-views');
        }
    }

    /**
     * Register other package's resources.
     *
     * @return void
     */
    private function registerResources()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views/', 'webauthn');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'webauthn');
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/webauthn.php', 'webauthn'
        );

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\PublishCommand::class,
            ]);
        }
    }
}
