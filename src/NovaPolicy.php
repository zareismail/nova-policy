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

        if(is_null(Gate::getPolicyFor($arguments[0]))) {
            // If policy not exists
            return null;
        }

        if($user->hasPermission(Helper::formatAbilityToPermission($arguments[0], $ability))) {
            // if ability defined via policy
            return true;
        } 

        if(! ($arguments[0] instanceof Ownable)) {
            // not ownable
            return false;
        }

        if(! is_null($arguments[0]->getKey()) && $user->isNot($arguments[0]->owner)) {
            // If the model created and has the wrong owner  
            // If the model was not created, we'll check permission for owner
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

        return $user->hasPermission(Helper::formatOwnableAbility(
            Helper::formatAbilityToPermission($arguments[0], $ability)
        )); 
    }
}