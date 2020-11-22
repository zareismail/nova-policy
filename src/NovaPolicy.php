<?php 

namespace Zareismail\NovaPolicy;

use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Zareismail\NovaPolicy\Contracts\{Authenticator, Ownable};
 
class NovaPolicy implements Authenticator
{  
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $app;

    /**
     * Create a new authenticator instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Determine if the given ability should be granted for the given user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return \Illuminate\Auth\Access\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorize($user, $ability, $arguments = []) 
    { 
        if(boolval(config('nova-policy.ignore', false))) {
            // Ignore managing access via configurations
            return null;
        }

        if(method_exists($user, 'isDeveloper') && $user->isDeveloper()) {
            // developer access
            return true;
        }

        if($user->hasPermission(Helper::BLOCKED)) {
            // wildcard restriction
            return false;
        }

        if($user->hasPermission(Helper::WILD_CARD)) {
            // wildcard access
            return true;
        }

        if(! isset($arguments[0]) || ! is_subclass_of($arguments[0], Model::class)) { 
            // if ability defined out of the policy
            return $user->hasPermission($ability);
        }   

        if(is_null(Gate::getPolicyFor($arguments[0]))) {
            // If policy not exists
            return null;
        }

        // check global action ability 
        if($user->hasPermission($ability)) {
            // if has wildcard ability on the model
            return true;
        } 

        if($user->hasPermission(Helper::formatPartialAbility($arguments[0]))) {
            // if has wildcard ability on the model
            return true;
        } 

        if($user->hasPermission(Helper::formatAbility($arguments[0], $ability))) {
            // if ability defined via policy
            return true;
        }  

        if(! Helper::isOwnable($arguments[0])) {
            // not ownable
            return false;
        } 

        if( ! Helper::isWithoutModelAbility($arguments[0], $ability) && 
            $user->isNot(optional($arguments[0])->owner)
        ){
            // If the model created and has the wrong owner   
            return false;
        }

        if($user->hasPermission(Helper::WILD_CARD_OWNABLE)) {
            // wildcard ownable access
            return true;
        } 

        // check global ownable action ability 
        if($user->hasPermission(Helper::formatAbilityOwner($ability))) {
            // if has wildcard ability on the model
            return true;
        }  

        if($user->hasPermission(Helper::formatOwnableAbility($arguments[0]))) {
            // wildcard ownable resriction 
            return true;
        }  
 
        return $user->hasPermission(Helper::formatAbility(
            $arguments[0], Helper::formatAbilityOwner($ability)
        )); 
    }
}