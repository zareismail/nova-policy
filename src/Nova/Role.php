<?php 

namespace Zareismail\NovaPolicy\Nova;
 
use Laravel\Nova\Http\Requests\NovaRequest;
use Illuminate\Http\Request;  
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;       
use Laravel\Nova\Fields\Heading;       
use Laravel\Nova\Fields\BooleanGroup;       
use Laravel\Nova\Panel;       
use Zareismail\RadioField\RadioButton;
use Illuminate\Support\Str;
use Laravel\Nova\Nova;
use Zareismail\NovaPolicy\Helper; 
use Zareismail\NovaPolicy\PolicyPermission; 
use Zareismail\NovaPolicy\Contracts\Ownable; 

class Role extends Resource
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
        $policies = Helper::policyInformation(); 

        $ownables = $this->formatOwnables($policies->map->permissions->flatten(1)->where('ownable'));

        $customToggle = $this->formatToggleKeys($policies->map->permissionKey);

        return array_merge([
            ID::make()->sortable(),

            Text::make(__('Role Name'), 'name')
                ->required()
                ->rules('required'),

            Text::make(__('Help Text'), 'help')
                ->nullable(),

            RadioButton::make(__('Resource Access'), 'access')
                ->options([
                    'own' => __('Access to own resource'), 
                    'custom' => __('Custom Access'), 
                    'none' => __('Without Restriction'),
                ])
                ->required()
                ->rules('required')
                ->stack()
                ->fillUsing([$this, 'fillPermissionsFromRequest'])
                ->resolveUsing(function() use ($ownables) { 
                    if(app(NovaRequest::class)->isCreateOrAttachRequest()) {
                        return 'own';
                    } 

                    $ownablePermissions = $this->permissions->whereIn(
                        'name', $ownables->keys()->push(Helper::NONE_OWNABLE)->all()
                    );

                    if($ownablePermissions->count() > 0) {
                        return 'own';
                    }

                    return $this->permissions->where('name', Helper::WILD_CARD_PERMISSION)->count() ? 'none' : 'custom';
                }) 
                ->help(__("Be careful; a user without restriction can access to everything!"))
                ->toggle([
                    'own' => $customToggle->all(),
                    'none'=> $customToggle->push('own')->push('abilities')->all(),
                    'custom' =>  ['own'],
                ]),

            $this->when($ownables->count(), function() use ($ownables) {
                return BooleanGroup::make(__('Give the owner user permission to:'), 'own')   
                            ->options($ownables)
                            ->fillUsing([$this, 'withoutFillUsing'])
                            ->resolveUsing([$this, 'booleanGroupResolveUsing'])
                            ->canSee(function($request) use ($ownables) {
                                $permissions = optional($this->resource)->permissions;

                                if($request->editing || is_null($permissions)) {
                                    return true;
                                }  

                                return $permissions->whereIn(
                                    'name', $ownables->keys()->push(Helper::NONE_OWNABLE)->all()
                                )->count() > 0;
                            })
                            ->help(__('User can do these actions on own-created resources'))
                            ->hideFromIndex();
            }),  

            BooleanGroup::make(__("Give each user ability to:"), 'abilities')
                ->options(Helper::abilities())
                ->fillUsing([$this, 'withoutFillUsing'])
                ->resolveUsing([$this, 'booleanGroupResolveUsing'])
                ->hideFromIndex()
                ->canSee(function($request) {
                    $permissions = optional($this->resource)->permissions;

                    if(count(Helper::abilities()) === 0) {
                        return false;
                    }

                    if($request->editing || is_null($permissions)) {
                        return true;
                    } 

                    return is_null($permissions->where('name', Helper::WILD_CARD_PERMISSION)->first());
                }),
                
        ], $policies->map([$this, 'permissionFields'])->values()->toArray());
    }    

    public function permissionFields($policy, $model)
    { 
        $ownables = is_subclass_of($model, Ownable::class) && collect($policy['permissions'])->where('ownable')->count();

        $permissions = collect($policy['permissions'])->map(function($permission) use ($policy) { 
            $ability = Helper::formatStringsToAbility([
                $policy['permissionKey'], $permission['name']
            ]);

            return [
                'name' => Str::title(Str::snake($permission['name'], ' ')),
                'custom' => $ability,
                'ownable' => $permission['ownable'] ? Helper::formatOwnableAbility($ability) : null, 
            ];
        });

        return new Panel(__('Restrictions On :resource', ['resource' => $policy['label']]), [    
            BooleanGroup::make(__("Give each user permission to:"), "{$policy['permissionKey']}-custom")
                ->options($permissions->pluck('name', 'custom')->all())
                ->fillUsing([$this, 'withoutFillUsing'])
                ->resolveUsing([$this, 'booleanGroupResolveUsing'])
                ->help(__('User can do these actions on each resource'))
                ->hideFromIndex(),
                
            $this->when($ownables, function() use ($policy, $permissions) {
                return BooleanGroup::make(__('Give the owner user permission to:'), "{$policy['permissionKey']}-own")
                            ->options($permissions->whereNotNull('ownable')->pluck('name', 'ownable')->all())
                            ->fillUsing([$this, 'withoutFillUsing'])
                            ->resolveUsing([$this, 'booleanGroupResolveUsing'])
                            ->help(__('User can do these actions on own-created resources'))
                            ->hideFromIndex();
            }),

        ]);
    }

    public function booleanGroupResolveUsing($value, $resource, $attribute)
    {
        return $resource->permissions->mapWithKeys(function($permission) {
            return [$permission->name => true];
        })->all();
    }

    public function withoutFillUsing()
    {
        
    }

    public function formatToggleKeys($permissionKeys)
    {
        return collect($permissionKeys)->flatMap(function($attribute) {
            return [
                $attribute, 
                "{$attribute}-own",
                "{$attribute}-custom",
            ];
        })->values();
    }

    public function formatOwnables($permissions)
    {
        return collect($permissions)->mapWithKeys(function($permission) {
            return [
                Helper::formatOwnableAbility($permission['name']) => $permission['name']
            ];
        });
    }

    public function fillPermissionsFromRequest($request, $model, $attribute, $requestAttribute)
    { 
        $model->saved(function($model) use ($request) {
            $abilities = [
                // without permission selection; all permissions will be restricted
                Helper::NONE_PERMISSION
            ]; 

            switch ($request->get('access')) {
                case 'own':
                    $abilities = $this->fetchOwnPermissionsFromRequest($request);
                    break;
                case 'none':
                    $abilities = [Helper::WILD_CARD_PERMISSION];
                    break;
                case 'custom':
                    $abilities = $this->fetchCustomPermissionsFromRequest($request);
                    break;
                
                default: 
                    break;
            } 

            $abilities = array_merge($abilities, $this->fetchAbilitiesFromRequest($request));

            $authorizedPermissions = $this->filterAuthorizedPermissions( 
                $request, $this->findPermissions($abilities)
            );

            $model->permissions()->sync( 
                $authorizedPermissions->modelKeys() 
            ); 
        }); 
    }

    public function fetchAbilitiesFromRequest(Request $request)
    {
        return collect(json_decode($request->get('abilities', []), true))->filter()->keys()->all();
    }

    public function fetchOwnPermissionsFromRequest(Request $request)
    {
        $permissions = collect(json_decode($request->get('own', []), true)); 

        switch ($permissions->filter()->count()) { 
            case 0:
                return [Helper::NONE_OWNABLE];
                break;

            case $permissions->count():
                return [Helper::WILD_CARD_OWNABLE];
                break;
            
            default:
                return $permissions->filter()->keys()->toArray();
                break;
        } 
    }

    public function fetchCustomPermissionsFromRequest(Request $request)
    {
        return Helper::policyInformation()->map->permissionKey->flatMap(function($key) use ($request) { 
            $permissions = array_merge(
                (array) json_decode($request->get("{$key}-custom"), true),
                (array) json_decode($request->get("{$key}-own"), true)
            ); 

            return collect($permissions)->filter()->keys();
        })->toArray(); 
    } 

    public function findPermissions(array $abilities)
    {
        $this->ensurePermissions($abilities);

        return PolicyPermission::whereIn('name', $abilities)->get(); 
    }

    public function ensurePermissions(array $permissions)
    {  
        $remainingPermissions = collect($permissions)->diff(PolicyPermission::get()->map->name);

        if($remainingPermissions->isNotEmpty()) {
            PolicyPermission::insert($remainingPermissions->map(function($name) {
                return compact('name');
            })->all());
        }
    }

    public function filterAuthorizedPermissions($request, $permissions)
    {
        return $permissions->filter(function ($model) use ($request) {
            return $this->authorizedToAttach($request, $model);
        });
    }
}