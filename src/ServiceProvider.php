<?php

namespace Zareismail\NovaPolicy;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Laravel\Nova\Nova as LaravelNova;

class ServiceProvider extends LaravelServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->registerPublishing();
            $this->loadMigrations();
        }

        $this->registerEvents();
        $this->registerPolicies();
        $this->registerRepository();
        $this->registerAuthenticator();
        LaravelNova::serving([$this, 'servingNova']);
        $this->loadJsonTranslationsFrom(__DIR__.'/../resources/lang');
    }

    /**
     * Serving the Nova application.
     */
    public function servingNova()
    {
        LaravelNova::resources([
            Nova\Permission::class,
        ]);
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing()
    {
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'nova-policy.migration');

        $this->publishes([
            __DIR__.'/../config/nova-policy.php' => config_path('nova-policy.php'),
        ], 'nova-policy.config');
    }

    /**
     * Load the package's migrations.
     *
     * @return void
     */
    public function loadMigrations()
    {
        $this->app->booted(function ($app) {
            if (config('nova-policy.migrations', true)) {
                $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
            }
        });
    }

    /**
     * Register the Auth models review events.
     *
     * @return void
     */
    public function registerEvents()
    {
        $this->app->booted(function () {
            collect(config('auth.providers'))->each(function ($provider) {
                $model = data_get($provider, 'model');

                if (isset($model) && method_exists($model, 'saved')) {
                    $model::saved(function ($model = null) {
                        if ($model instanceof \Illuminate\Contracts\Auth\Authenticatable) {
                            app(Contracts\Repository::class)->review($model);
                        }
                    });
                }
            });
        });
    }

    /**
     * Register the `NovaPolicy` policies.
     *
     * @return void
     */
    public function registerPolicies()
    {
        Gate::policy(PolicyRole::class, Policies\RolePolicy::class);

        Gate::before(function ($user, $ability, $arguments = []) {
            return app(Contracts\Authenticator::class)->authorize($user, $ability, $arguments);
        });
    }

    /**
     * Register the `NovaPolicy` authenticator.
     *
     * @return void
     */
    public function registerAuthenticator()
    {
        $this->app->singleton(Contracts\Authenticator::class, function ($app) {
            return new NovaPolicy($app[Contracts\Repository::class]);
        });
    }

    /**
     * Register the `NovaPolicy` repository.
     *
     * @return void
     */
    public function registerRepository()
    {
        $this->app->singleton(Contracts\Repository::class, function ($app) {
            return new Repository($app);
        });
    }
}
