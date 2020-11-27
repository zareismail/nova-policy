<?php 

namespace Zareismail\NovaPolicy;

use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\Authenticatable;
use Zareismail\NovaPolicy\Contracts\{Authenticator, Ownable, Repository};
 
class NovaPolicy implements Authenticator
{  
    /**
     * The application instance.
     *
     * @var \Zareismail\NovaPolicy\Contracts\Repository
     */
    protected $repository;

    /**
     * Create a new authenticator instance.
     *
     * @param  \Zareismail\NovaPolicy\Contracts\Repository  $repository
     * @return void
     */
    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
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

        if($this->forUser($user)->has(Helper::BLOCKED)) {
            // wildcard restriction
            return false;
        }

        if($this->forUser($user)->has(Helper::WILD_CARD)) {
            // wildcard access
            return true;
        }

        if(! isset($arguments[0]) || ! is_subclass_of($arguments[0], Model::class)) { 
            // if ability defined out of the policy
            return $this->forUser($user)->has($ability);
        }   

        if(is_null(Gate::getPolicyFor($arguments[0]))) {
            // If policy not exists
            return null;
        }

        // check global action ability 
        if($this->forUser($user)->has($ability)) {
            // if has wildcard ability on the model
            return true;
        } 

        if($this->forUser($user)->has(Helper::formatPartialAbility($arguments[0]))) {
            // if has wildcard ability on the model
            return true;
        } 

        if($this->forUser($user)->has(Helper::formatAbility($arguments[0], $ability))) {
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

        if($this->forUser($user)->has(Helper::WILD_CARD_OWNABLE)) {
            // wildcard ownable access
            return true;
        } 

        // check global ownable action ability 
        if($this->forUser($user)->has(Helper::formatAbilityOwner($ability))) {
            // if has wildcard ability on the model
            return true;
        }  

        if($this->forUser($user)->has(Helper::formatOwnableAbility($arguments[0]))) {
            // wildcard ownable resriction 
            return true;
        }  
 
        return $this->forUser($user)->has(Helper::formatAbility(
            $arguments[0], Helper::formatAbilityOwner($ability)
        )); 
    }

    /**
     * Get the permission repository for the given user.
     * 
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user 
     * @return \Zareismail\NovaPolicy\Contracts\Repository             
     */
    public function forUser(Authenticatable $user)
    {
        return $this->repository->for($user);
    }
}