<?php

namespace Zareismail\NovaPolicy;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PolicyPermissionRole extends Pivot
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'policy_permission_role';

    /**
     * Bootstrap the model and its traits.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($model) {
            $model->flushCache();
        });

        static::deleting(function ($model) {
            $model->flushCache();
        });
    }

    public function flushCache()
    {
        PolicyUserRole::with('user')
            ->where('policy_role_id', $this->policy_role_id)->get()
            ->each->flushCache();

        return $this;
    }
}
