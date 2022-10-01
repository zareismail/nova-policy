<?php

namespace Zareismail\NovaPolicy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PolicyRole extends Model
{
    use SoftDeletes;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Query the related permissions.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions()
    {
        return $this->belongsToMany(PolicyPermission::class, 'policy_permission_role')->using(PolicyPermissionRole::class);
    }

    /**
     * Sync the model with the given permissions.
     * 
     * @param  array  $permissions 
     * @return $this              
     */
    public function syncPermissions(array $permissions = [])
    {
        $this->permissions()->sync(PolicyPermission::sync($permissions));

        return $this;
    }
}
