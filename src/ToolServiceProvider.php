<?php

namespace Zareismail\NovaPolicy;
 
use Illuminate\Support\ServiceProvider; 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Laravel\Nova\Nova as LaravelNova; 
use Zareismail\NovaPolicy\Contracts\Authenticator;

class ToolServiceProvider extends ServiceProvider
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

        LaravelNova::serving([$this, 'servingNova']);  
        $this->registerPolicies();
        $this->registerAuthenticator();
    } 

    /**
     * Serving the Nova application.
     *  
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
            __DIR__.'/../database/migrations' => database_path('migrations')
        ], 'nova-policy.migration');

        $this->publishes([
            __DIR__.'/../config/nova-policy.php' => config_path('nova-policy.php')
        ], 'nova-policy.config');
    }

    /**
     * Load the package's migrations.
     *
     * @return void
     */
    public function loadMigrations()
    {
        $this->app->booted(function($app) {
            if(config('nova-policy.migrations', true)) {
                $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
            }
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

        Gate::before(function($user, $ability, $arguments = []) {
            return app(Authenticator::class)->authorize($user, $ability, $arguments);
        });
    } 

    /**
     * Register the `NovaPolicy` authenticator.
     *
     * @return void
     */
    public function registerAuthenticator()
    {
        $this->app->singleton(Authenticator::class, function($app) {
            return new NovaPolicy($app);
        });  
    }
}
