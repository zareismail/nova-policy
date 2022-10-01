<?php

namespace Zareismail\NovaPolicy;

use Illuminate\Database\Eloquent\Model;

class PolicyPermission extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name'];

    /**
     * Sync the given permissions with the database.
     *
     * @param  array  $permissions
     * @return Illuminate\Database\Eloquent\Collection
     */
    public static function sync(array $permissions)
    {
        $missings = collect(static::filterMissings($permissions))->map(function ($name) {
            return compact('name');
        });

        static::insert($missings->all());

        return static::whereIn('name', $permissions)->get();
    }

    /**
     * Filter permissions that not exists in the database.
     *
     * @param  array  $permissions
     * @return [type]
     */
    public static function filterMissings(array $permissions)
    {
        return collect($permissions)->diff(static::get()->map->name)->unique()->toArray();
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = [])
    {
        return new PermissionCollection($models);
    }
}
