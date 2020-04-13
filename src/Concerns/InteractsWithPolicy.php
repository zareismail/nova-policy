<?php 

namespace Zareismail\NovaPolicy\Concerns;

use Zareismail\NovaPolicy\PolicyPermission;
use Zareismail\NovaPolicy\PolicyRole;


trait InteractsWithPolicy 
{
	public function hasPermission(string $ability) : bool
	{
		$this->relationLoaded('roles.permissions') || $this->load('roles.permissions');
		$this->relationLoaded('permissions') || $this->load('permissions');

		$permissions = $this->roles->flatMap->permissions->map->name->merge(
			$this->permissions->map->name
		); 

		return $permissions->contains($ability);
	}

	public function permissions()
	{
		return $this->belongsToMany(PolicyPermission::class, 'policy_user_permission');
	}

	public function roles()
	{
		return $this->belongsToMany(PolicyRole::class, 'policy_user_role');
	}
}