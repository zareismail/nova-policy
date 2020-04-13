<?php 

namespace Zareismail\NovaPolicy;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Laravel\Nova\Nova;
 
class Helper
{
    public const NONE_PERMISSION = 'none';

    public const WILD_CARD_PERMISSION = '*';

    public const NONE_OWNABLE = 'own.none';

    public const WILD_CARD_OWNABLE = 'own.*';

	public static function policyInformation()
    {
        return collect(Gate::policies())->map(function($policy, $model) { 
            return [
                'label' => $label = static::guessModelLabel($model),
                'singularLabel' => Str::singular($label),
                'permissionKey' => static::guessPermissionKey($model),
                'permissions'   => static::guessPolicyAbilities($policy),
            ];
        });
    } 

    public static function abilities()
    {
        return collect(Gate::abilities())->map(function($callback, $ability) {
            return Str::snake($ability, ' ');
        })->all();
    }

    public static function guessModelLabel($model)
    {
    	if($resourceClass = Nova::resourceForModel($model)) {
    		return $resourceClass::label();
    	}

    	return Str::plural(Str::title(Str::snake(class_basename($model), ' ')));
    }

    public static function guessPermissionKey($class)
    {
        return Str::kebab(class_basename($class));
    }

    public static function formatAbilityToPermission($class, $ability)
    {
        return static::guessPermissionKey($class). ".{$ability}";
    }

    public static function guessPolicyAbilities($policy)
    { 
        return collect(static::reflectionMethods($policy))->map(function($method) {
            return [
                'name' => $method->name,
                'ownable' => $method->getNumberOfParameters() > 1,
            ]; 
        });
    }

    public static function reflectionMethods($class)
    {
        return (new \ReflectionClass($class))->getMethods(\ReflectionMethod::IS_PUBLIC);
    } 

    public static function formatStringsToAbility(array $strings)
    {
        return implode('.', $strings);
    }

    public static function formatOwnableAbility(string $ability)
    {
        return "{$ability}.own";
    }
}