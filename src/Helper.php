<?php 

namespace Zareismail\NovaPolicy;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Laravel\Nova\Nova; 
 
class Helper
{
    /**
     * Blocked ability string.
     * 
     * @param  string
     */
    public const BLOCKED = 'none';

    /**
     * Ownable ability suffix.
     * 
     * @param  string
     */
    public const OWNABLE = 'owner';   

    /**
     * Permitted abilities identifier.
     * 
     * @param  string
     */
    public const PERMITTED = 'permitted';
    
    /**
     * Action ability string.
     * 
     * @param  string
     */
    public const ACTION = 'action';

    /**
     * Wild card ability string.
     * 
     * @param  string
     */
    public const WILD_CARD = '*';

    /**
     * Partial ability string.
     * 
     * @param  string
     */
    public const WILD_CARD_PARTIAL = 'partial'; 

    /**
     * Wild card ownable ability string.
     * 
     * @param  string
     */
    public const WILD_CARD_OWNABLE = 'own';   

    /**
     * Get the available resources for the authorization.
     * 
     * @return array
     */
    public static function resources()
    {
        return collect(Gate::policies())->map(function($policy, $model) {    
            return [
                'key' => Str::kebab(class_basename($model)),
                'model' => $model,
                'label' => static::modelLabel($model),
                'ownable' => static::isOwnable($model), 
            ]; 
        })->filter()->values()->sortBy('label')->all();
    } 

    /**
     * Get the available ownable resources.
     * 
     * @return array
     */
    public static function ownableResources()
    {
        return collect(static::resources())->where('ownable', true)->map(function($resource) {
            return array_merge($resource, [
                'key' => static::formatOwnableAbility($resource['model']),
            ]);
        });
    } 

    /**
     * Get the available resources for the partial authorization.
     * 
     * @return array
     */
    public static function wildcardPartialResources()
    {
        return collect(static::resources())->map(function($resource) {
            return array_merge($resource, [
                'key' => static::formatPartialAbility($resource['model']),
            ]);
        });
    } 

    /**
     * Get the available actions for authorization.
     * 
     * @return array
     */
    public static function actions()
    {
        return collect(Gate::policies())
                    ->flatMap([static::class, 'policyAbilities'])
                    ->unique()->values()->sortDesc()->all();
    }

    /** 
     * Get the grouped abilities.
     *  
     * @return array
     */
    public static function groupedAbilities()
    {
        return collect(Gate::policies())->map(function($policy, $model) { 
            return [
                'key' => Str::kebab(class_basename($model)), 
                'group' => static::modelLabel($model), 
                'abilities' => static::policyAbilities($policy, $model)->map(function($ability) use ($model) {
                    return [
                        'key' => static::formatAbility($model, $ability),
                        'label' => static::abilityLabel($ability), 
                    ];
                }),
            ];
        })->sortBy('group')->prepend(static::definedAbilities())->all();
    } 

    /**
     * Get globaly defined abilities.
     * 
     * @return array
     */
    public static function definedAbilities()
    { 
        return [
            'key' => 'abilities',
            'group' => __('Other abilities'), 
            'abilities' => collect(Gate::abilities())->keys()->map(function($ability) {
                return [
                    'key' => $ability,
                    'label' => static::abilityLabel($ability)
                ];
            })->all()
        ]; 
    }

    /**
     * Get defined ability on the policy.
     * 
     * @param  string $policy
     * @param  string $model 
     * @return array        
     */
    public static function policyAbilities($policy, $model)
    {
        return collect(static::publicMethods($policy))->flatMap(function($method) use ($model) {
            return array_filter([
                $method->name, 
                static::isOwnable($model) && $method->getNumberOfParameters() > 1 
                    ? static::formatAbilityOwner($method->name) : null 
            ]);
        })->values()->unique()->sortDesc();
    } 

    /**
     * Detect if the given class implements Ownable.
     * 
     * @param  string $model 
     * @return boolean        
     */
    public static function isOwnable($model)
    { 
        return collect(class_implements($model))->contains(Contracts\Ownable::class);
    } 

    /**
     * Detect if the given class uses InteractsWithPolicy.
     * 
     * @param  mixed $model 
     * @return boolean        
     */
    public static function isAuthorizable($model) : bool
    {  
        return in_array(Concerns\InteractsWithPolicy::class, class_uses_recursive($model));
    } 

    /**
     * Get the model label.
     * 
     * @param  string $model 
     * @return string        
     */
    public static function modelLabel($model)
    {
    	if($resourceClass = Nova::resourceForModel($model)) {
    		return $resourceClass::label();
    	}

    	return Str::plural(Str::title(Str::snake(class_basename($model), ' ')));
    }

    /**
     * Get the ability label.
     * 
     * @param  string $ability
     * @return string         
     */
    public static function abilityLabel($ability)
    {  
        return Str::snake(Str::replaceArray(static::OWNABLE, [' [own genereated resource]'], $ability), ' ');
    } 

    /**
     * Get the class public methods.
     * 
     * @param  string $class 
     * @return array        
     */
    public static function publicMethods($class)
    {
        return (new \ReflectionClass($class))->getMethods(\ReflectionMethod::IS_PUBLIC);
    }  

    /**
     * Format the partial ability string for the given model.
     * 
     * @param  string $model 
     * @return string        
     */
    public static function formatOwnableAbility($model)
    { 
        return implode('.', [Helper::OWNABLE, static::abilityKey($model)]);
    }

    /**
     * Format the partial ability string for the given model.
     * 
     * @param  string $model 
     * @return string        
     */
    public static function formatPartialAbility($model)
    { 
        return implode('.', [static::abilityKey($model), Helper::WILD_CARD]);
    }

    /**
     * Format the ability string for the given model.
     * 
     * @param  string $model 
     * @param  string $ability 
     * @return string        
     */
    public static function formatAbility($model, $ability)
    { 
        return implode('.', [static::abilityKey($model), $ability]);
    }

    /**
     * Format the ability string for the given model.
     *  
     * @param  string $ability 
     * @return string        
     */
    public static function formatAbilityOwner($ability)
    { 
        return implode('.', [$ability, static::OWNABLE]);
    }

    /**
     * Make the ability key for the given model.
     * 
     * @param  $model 
     * @return        
     */
    public static function abilityKey($model)
    {
        return md5(is_object($model) ? get_class($model) : $model);
    }

    /**
     * Detect if policy method is without model action.
     * 
     * @param  mixed  $model   
     * @param  string  $ability 
     * @return boolean          
     */
    public static function isWithoutModelAbility($model, $ability)
    {
        if($policy = Gate::getPolicyFor($model)) {
            $method = collect(static::publicMethods($policy))->where('name', $ability)->first();

            return optional($method)->getNumberOfParameters() < 2;
        }

        return false;
    }
}