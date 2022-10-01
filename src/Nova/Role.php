<?php

namespace Zareismail\NovaPolicy\Nova;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\BooleanGroup;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Zareismail\NovaPolicy\Helper;

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
        'permissions',
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

            Text::make(__('Help Text'), 'help')->nullable(),

            Select::make(__('Has Access To'), 'level')
                ->options([
                    Helper::BLOCKED => [
                        'label' => __('Nothing'),
                        'group' => __('Wildcard'),
                    ],
                    Helper::OWNABLE => [
                        'label' => __('Some of the own-generated resources'),
                        'group' => __('Owner'),
                    ],
                    Helper::WILD_CARD_OWNABLE => [
                        'label' => __('All of the own-generated resources'),
                        'group' => __('Owner'),
                    ],
                    Helper::WILD_CARD_PARTIAL => [
                        'label' => __('Some of the generated resources'),
                        'group' => __('Partial'),
                    ],
                    Helper::ACTION => [
                        'label' => __('Some of the available actions'),
                        'group' => __('Partial'),
                    ],
                    Helper::PERMITTED => [
                        'label' => __('Somethings'),
                        'group' => __('Partial'),
                    ],
                    Helper::WILD_CARD => [
                        'label' => __('Everything'),
                        'group' => __('Wildcard'),
                    ],
                ])
                ->resolveUsing(function ($value, $resource, $attribute) {
                    if (
                        is_null($resource->permissions) ||
                        $resource->permissions->isEmpty() ||
                        $resource->permissions->isBlocked()
                    ) {
                        return Helper::BLOCKED;
                    }

                    if ($resource->permissions->isWildcardOwnable()) {
                        return Helper::WILD_CARD_OWNABLE;
                    }

                    if ($resource->permissions->isWildcard()) {
                        return Helper::WILD_CARD;
                    }

                    if ($resource->permissions->isAction()) {
                        return Helper::ACTION;
                    }

                    if ($resource->permissions->isOwnable()) {
                        return Helper::OWNABLE;
                    }

                    if ($resource->permissions->isPartial()) {
                        return Helper::WILD_CARD_PARTIAL;
                    }

                    return Helper::PERMITTED;
                })
                ->fillUsing(function ($request, $model, $attribute, $requestAttribute) {
                    return function () use ($model, $request) {
                        $model->syncPermissions((array) $this->fetchTheLevelPermissions(
                            $request,
                            $request->get('level')
                        ));
                    };
                }),

            Textarea::make(__('Permission Help'))
                ->readonly()
                ->dependsOn(['level'], function ($field, $request, $formData) {
                    $field->value = data_get([
                        Helper::BLOCKED => __('The user will be out of access to any things.'),
                        Helper::WILD_CARD_OWNABLE => __('Be careful; With this permission, the user can take any action regarding their generated resources.'),
                        Helper::OWNABLE => __('Be careful on select the following; this allows the user to take any action regarding their generated resources.'),
                        Helper::WILD_CARD => __('Warning! this allows user to do anything.'),
                        Helper::ACTION => __('Be careful; This allows the user to take selective action on the any resource.'),
                        Helper::WILD_CARD_PARTIAL => __('Watch out; this allows the user to take any action regarding to the selected resources.'),
                        Helper::PERMITTED => __('So good, you can fully customize the user accesses.'),

                    ], $formData->level);
                }),

            BooleanGroup::make(__('Owner Has Access To'), Helper::OWNABLE)
                ->options(collect(Helper::ownableResources())->pluck('label', 'key'))
                ->fillUsing([$this, 'fillUPermissions'])
                ->resolveUsing([$this, 'resolvePermisisons'])
                ->onlyOnForms()
                ->dependsOn(['level'], function ($field, $request, $formData) {
                    $formData->level !== Helper::OWNABLE ? $field->hide() : $field->show();
                }),

            BooleanGroup::make(__('Access To All'), Helper::WILD_CARD_PARTIAL)
                ->options(collect(Helper::wildcardPartialResources())->pluck('label', 'key'))
                ->fillUsing([$this, 'fillUPermissions'])
                ->resolveUsing([$this, 'resolvePermisisons'])
                ->onlyOnForms()
                ->dependsOn(['level'], function ($field, $request, $formData) {
                    $formData->level !== Helper::WILD_CARD_PARTIAL ? $field->hide() : $field->show();
                }),

            $this->merge(collect(Helper::groupedAbilities())->flatMap(function ($group) {
                return [
                    BooleanGroup::make(__($group['group']), Helper::PERMITTED."[{$group['key']}]")
                        ->options(collect($group['abilities'])->pluck('label', 'key'))
                        ->help(__('User can take this actions on the :group', ['group' => $group['group']]))
                        ->fillUsing(function () {
                        })
                        ->resolveUsing([$this, 'resolvePermisisons'])
                        ->onlyOnForms()
                        ->dependsOn(['level'], function ($field, $request, $formData) {
                            $formData->level !== Helper::PERMITTED ? $field->hide() : $field->show();
                        }),
                ];
            })->all()),

            BooleanGroup::make(__('Can'), Helper::ACTION)
                ->options(collect(Helper::actions())->mapWithKeys(function ($action) {
                    return [
                        $action => __(Str::replaceArray('.'.Helper::OWNABLE, [' [own genereated]'], $action).' resources'),
                    ];
                }))
                ->fillUsing(function () {
                })
                ->resolveUsing([$this, 'resolvePermisisons'])
                ->onlyOnForms()
                ->dependsOn(['level'], function ($field, $request, $formData) {
                    $formData->level !== Helper::ACTION ? $field->hide() : $field->show();
                }),
        ];
    }

    /**
     * Get the permisison from the request with the given level.
     *
     * @param  \Illuminate\Http\Request  $request
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
                return collect($request->get('permissions'))->flatMap(function ($permissions) {
                    return array_keys(array_filter(json_decode($permissions, true)));
                })->values()->all();
                break;

            default:
                return [];
                break;
        }
    }

    /**
     * Fillmodel with the permissions.
     *
     * @param  mixed  $value
     * @param  mixed  $resource
     * @param  string  $attribute
     * @return  array
     */
    public function fillUPermissions($request, $model, $attribute, $requestAttribute)
    {
        if (in_array($level = $request->get('level'), [
            Helper::BLOCKED,
            Helper::WILD_CARD,
            Helper::WILD_CARD_OWNABLE,
        ])) {
            return;
        }

        return function () use ($model, $request) {
            $model->syncPermissions(array_keys(array_filter(json_decode(
                $request->get($level, '[]'),
                true
            ))));
        };
    }

    /**
     * Resolve the permission via true false value.
     *
     * @param  mixed  $value
     * @param  mixed  $resource
     * @param  string  $attribute
     * @return  array
     */
    public function resolvePermisisons($value, $resource, $attribute)
    {
        return collect($this->resource->permissions)->keyBy->name->map(function ($permission) {
            return boolval($permission->id);
        })->all();
    }
}
