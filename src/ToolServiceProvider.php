<?php

namespace Zareismail\NovaPolicy;
 
use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Events\ServingNova;
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
        LaravelNova::serving(function (ServingNova $event) {
            LaravelNova::resources([
                Nova\Role::class,
            ]);
        }); 
    } 

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        \Gate::policy(PolicyRole::class, Policies\RolePolicy::class); 

        \Gate::before(function($user, $ability, $arguments = []) { 
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

            if($user->isNot($arguments[0]->owner())) {
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
