<?php

namespace Zareismail\NovaPolicy;
 
use Illuminate\Support\ServiceProvider; 
use Laravel\Nova\Nova as LaravelNova; 
use Illuminate\Database\Eloquent\Model;

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
        }

        LaravelNova::serving([$this, 'servingNova']); 

        $this->registerPolicies();
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
        ], 'nova-policy.migration');
    }

    /**
     * Register NovaPolicy policies.
     *
     * @return void
     */
    public function registerPolicies()
    {
        \Gate::policy(PolicyRole::class, Policies\RolePolicy::class); 

        \Gate::before(function($user, $ability, $arguments = []) { 
            if(config('nova-policy.ignore', false) === true) {
                // Ignore managing access
                return null;
            }

            if(method_exists($user, 'isDeveloper') && $user->isDeveloper()) {
                // developer access
                return true;
            }

            if($user->hasPermission(Helper::WILD_CARD_PERMISSION)) {
                // wildcard access
                return true;
            }

            if($user->hasPermission(Helper::NONE_PERMISSION)) {
                // wildcard restriction
                return false;
            }

            if(! isset($arguments[0]) || ! is_subclass_of($arguments[0], Model::class)) { 
                // if ability defined out of the policy
                return $user->hasPermission($ability);
            }   

            if($user->hasPermission(Helper::formatAbilityToPermission($arguments[0], $ability))) {
                // if ability defined out of the policy
                return true;
            } 

            if(! ($arguments[0] instanceof Contracts\Ownable)) {
                // not ownable
                return false;
            }

            if($user->isNot($arguments[0]->owner)) {
                // wrong owner
                return false;
            }

            if($user->hasPermission(Helper::WILD_CARD_OWNABLE)) {
                // wildcard ownable access
                return true;
            }

            if($user->hasPermission(Helper::NONE_OWNABLE)) {
                // wildcard ownable resriction 
                return false;
            }

            return $this->hasPermission(Helper::formatOwnableAbility(
                Helper::formatAbilityToPermission($arguments[0], $ability)
            )); 
        });
    }
}
