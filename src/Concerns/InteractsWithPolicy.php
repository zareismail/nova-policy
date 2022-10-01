<?php

namespace Zareismail\NovaPolicy\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Zareismail\NovaPolicy\PolicyPermission;
use Zareismail\NovaPolicy\PolicyRole;
use Zareismail\NovaPolicy\PolicyUserPermission;
use Zareismail\NovaPolicy\PolicyUserRole;

trait InteractsWithPolicy
{
    /**
     * Query the related Permission`s.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->morphToMany(PolicyPermission::class, 'user', 'policy_user_permission', 'user_id')
                    ->using(PolicyUserPermission::class);
    }

    /**
     * Query the related Permission`s.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->morphToMany(PolicyRole::class, 'user', 'policy_user_role', 'user_id')
                    ->using(PolicyUserRole::class);
    }
}
