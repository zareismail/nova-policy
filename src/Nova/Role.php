<?php 

namespace Zareismail\NovaPolicy\Nova;
 
use Illuminate\Support\Str;
use Illuminate\Http\Request;  
use Laravel\Nova\Nova;    
use Laravel\Nova\Panel;       
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\{ID, Text, Select, Heading, BooleanGroup, BelongsToMany}; 
use Armincms\Fields\Chain;  
use Zareismail\NovaPolicy\{Helper, PolicyPermission, Contracts\Ownable};  

abstract class Role extends Resource
{ 
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = 'Zareismail\\NovaPolicy\\PolicyRole';  

    /**
     * The relationships that should be eager loaded when performing an index query.
     *
     * @var array
     */
    public static $with = [
        'permissions'
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {    
        return [
            ID::make()->sortable(),

            Text::make(__('Role Name'), 'name')
                ->required()
                ->rules('required'),

            Text::make(__('Help Text'), 'help')
                ->nullable(),

            Chain::make('levels', function() {
                return [
                    Select::make(__('Has Access To'), 'level')
                        ->options([
                            Helper::BLOCKED => __('Nothing'),
                            Helper::OWNABLE => __('Some of the own-generated resources'),
                            Helper::WILD_CARD_OWNABLE => __('All of the own-generated resources'),
                            Helper::WILD_CARD_PARTIAL => __('Some of the generated resources'), 
                            Helper::ACTION => __('Some of the available actions'), 
                            Helper::PERMITTED => __('Somethings'),
                            Helper::WILD_CARD => __('Everything'),
                        ])
                        ->resolveUsing(function($value, $resource, $attribute) { 
                            if(is_null($resource->permissions) ||
                               $resource->permissions->isEmpty() || 
                               $resource->permissions->isBlocked()
                            ){
                                return Helper::BLOCKED;
                            }

                            if($resource->permissions->isWildcardOwnable()) {
                                return Helper::WILD_CARD_OWNABLE;
                            }

                            if($resource->permissions->isWildcard()) {
                                return Helper::WILD_CARD;
                            } 

                            if($resource->permissions->isAction()) {
                                return Helper::ACTION;
                            }

                            if($resource->permissions->isOwnable()) {
                                return Helper::OWNABLE;
                            } 

                            if($resource->permissions->isPartial()) {
                                return Helper::WILD_CARD_PARTIAL;
                            } 

                            return Helper::PERMITTED; 
                        })
                        ->fillUsing(function($request, $model, $attribute, $requestAttribute) {
                            $model::saved(function($model) use ($request) {
                                $model->syncPermissions((array) $this->fetchTheLevelPermissions(
                                    $request, $request->get('level')
                                )); 
                            });
                        })
                ];
            }), 

            Chain::with('levels', function($request) {
                switch ($request->get('level')) {
                    case Helper::BLOCKED:
                        $help = __('The user will be out of access to any things.');
                        break;

                    case Helper::WILD_CARD_OWNABLE:
                        $help = __('Be careful; With this permission, the user can take any action regarding their generated resources.');
                        break;

                    case Helper::OWNABLE:
                        $help = __('Be careful on select the following; this allows the user to take any action regarding their generated resources.');
                        break;

                    case Helper::WILD_CARD:
                        $help = __('Warning! this allows user to do anything.');
                        break;

                    case Helper::ACTION:
                        $help = __('Be careful; This allows the user to take selective action on the any resource.');
                        break;

                    case Helper::WILD_CARD_PARTIAL:
                        $help = __('Watch out; this allows the user to take any action regarding to the selected resources.');
                        break;

                    case Helper::PERMITTED:
                        $help = __('So good, you can fully customize the user accesses.');
                        break;
                    
                    default:
                        return [];
                        break;
                }
                return [
                    Heading::make($help),
                ];
            }),

            Chain::with('levels', function($request) { 
                switch ($request->get('level')) {
                    case Helper::ACTION:
                        return static::actions($request);
                        break; 

                    case Helper::OWNABLE:
                        return static::ownableResources($request);
                        break; 

                    case Helper::WILD_CARD_PARTIAL:
                        return static::resources($request);
                        break; 

                    case Helper::PERMITTED:
                        return static::abilities($request);
                        break; 
                    
                    default:
                        return [];
                        break;
                }
            }),
        ];
    }    

    /**
     * Get the permisison from the request with the given level.
     * 
     * @param  \Illuminate\Http\Request $request
     * @param  string  $level  
     * @return array          
     */
    public function fetchTheLevelPermissions(Request $request, string $level)
    {    
        switch ($level) {
            case Helper::BLOCKED:
            case Helper::WILD_CARD:
            case Helper::WILD_CARD_OWNABLE:
                return $level;
                break; 
 
            case Helper::PERMITTED:
                return collect($request->get('permissions'))->flatMap(function($permissions) {
                    return array_keys(array_filter(json_decode($permissions, true)));
                })->values()->all(); 
                break; 

            default:
                return [];
                break;
        } 
    }

    /**
     * Get the actions selection field.
     * 
     * @param  \Illuminate\Http\Request $request 
     * @return array           
     */
    public function actions(Request $request)
    {
        return [
            BooleanGroup::make(__('Can'), 'permissions')
                ->options(collect(Helper::actions())->mapWithKeys(function($action) {  
                    return [
                        $action => __(Str::replaceArray('.'.Helper::OWNABLE, [' [own genereated]'], $action). ' resources'),
                    ];
                }))
                ->fillUsing([$this, 'fillUPermissions'])
                ->resolveUsing([$this, 'resolvePermisisons']),
        ]; 
    } 

    /**
     * Get the ownable resource selection field.
     * 
     * @param  \Illuminate\Http\Request $request 
     * @return array           
     */
    public function ownableResources(Request $request)
    {
        return [
            BooleanGroup::make(__('Owner Has Access To'), 'permissions')
                ->options(collect(Helper::ownableResources())->pluck('label', 'key'))
                ->fillUsing([$this, 'fillUPermissions'])
                ->resolveUsing([$this, 'resolvePermisisons']),
        ]; 
    } 

    /**
     * Get the resource selection field.
     * 
     * @param  \Illuminate\Http\Request $request 
     * @return array           
     */
    public function resources(Request $request)
    {
        return [
            BooleanGroup::make(__('Access To All'), 'permissions')
                ->options(collect(Helper::wildcardPartialResources())->pluck('label', 'key'))
                ->fillUsing([$this, 'fillUPermissions'])
                ->resolveUsing([$this, 'resolvePermisisons']),
        ]; 
    } 

    /**
     * Get the ability selection field.
     * 
     * @param  \Illuminate\Http\Request $request 
     * @return array           
     */
    public function abilities()
    {
        return collect(Helper::groupedAbilities())->flatMap(function($group) {
            return [ 
                BooleanGroup::make(__($group['group']), "permissions[{$group['key']}]")
                    ->options(collect($group['abilities'])->pluck('label', 'key'))
                    ->help(__('User can take this actions on the :group', ['group' => $group['group']]))
                    ->fillUsing(function() { })
                    ->resolveUsing([$this, 'resolvePermisisons']),
            ];
        })->all();
    }

    /**
     * Fillmodel with the permissions.
     * 
     * @param mixed $value     
     * @param mixed  $resource  
     * @param string  $attribute
     *  
     * @return  array           
     */
    public function fillUPermissions($request, $model, $attribute, $requestAttribute)
    {
        $model::saved(function($model) use ($request) {
            $model->syncPermissions(array_keys(array_filter(json_decode(
                $request->get('permissions', '[]'), true
            )))); 
        });
    }

    /**
     * Resolve the permission via true false value.
     * 
     * @param mixed $value     
     * @param mixed  $resource  
     * @param string  $attribute
     *  
     * @return  array           
     */
    public function resolvePermisisons($value, $resource, $attribute)
    {
        if($permissions = $this->resource->permissions) {
            return $permissions->keyBy('name')->map(function($permission) {
                return boolval($permission->id);
            })->all(); 
        }
    }
}