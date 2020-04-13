<?php

namespace Zareismail\NovaPolicy\Policies;

use App\Zareismail\NovaPolicy\PolicyRole;
use Illuminate\Auth\Access\HandlesAuthorization;
use Zareismail\Contracts\User;

class RolePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any policy roles.
     *
     * @param  \Zareismail\Contracts\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        //
    }

    /**
     * Determine whether the user can view the policy role.
     *
     * @param  \Zareismail\Contracts\User  $user
     * @param  \App\Zareismail\NovaPolicy\PolicyRole  $policyRole
     * @return mixed
     */
    public function view(User $user, PolicyRole $policyRole)
    {
        //
    }

    /**
     * Determine whether the user can create policy roles.
     *
     * @param  \Zareismail\Contracts\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the policy role.
     *
     * @param  \Zareismail\Contracts\User  $user
     * @param  \App\Zareismail\NovaPolicy\PolicyRole  $policyRole
     * @return mixed
     */
    public function update(User $user, PolicyRole $policyRole)
    {
        //
    }

    /**
     * Determine whether the user can delete the policy role.
     *
     * @param  \Zareismail\Contracts\User  $user
     * @param  \App\Zareismail\NovaPolicy\PolicyRole  $policyRole
     * @return mixed
     */
    public function delete(User $user, PolicyRole $policyRole)
    {
        //
    }

    /**
     * Determine whether the user can restore the policy role.
     *
     * @param  \Zareismail\Contracts\User  $user
     * @param  \App\Zareismail\NovaPolicy\PolicyRole  $policyRole
     * @return mixed
     */
    public function restore(User $user, PolicyRole $policyRole)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the policy role.
     *
     * @param  \Zareismail\Contracts\User  $user
     * @param  \App\Zareismail\NovaPolicy\PolicyRole  $policyRole
     * @return mixed
     */
    public function forceDelete(User $user, PolicyRole $policyRole)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the policy role.
     *
     * @param  \Zareismail\Contracts\User  $user
     * @param  \App\Zareismail\NovaPolicy\PolicyRole  $policyRole
     * @return mixed
     */
    public function attachPolicyPermission(User $user, PolicyRole $policyRole)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the policy role.
     *
     * @param  \Zareismail\Contracts\User  $user 
     * @return mixed
     */
    public function attachAnyPolicyPermission(User $user)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the policy role.
     *
     * @param  \Zareismail\Contracts\User  $user
     * @param  \App\Zareismail\NovaPolicy\PolicyRole  $policyRole
     * @return mixed
     */
    public function detachPolicyPermission(User $user, PolicyRole $policyRole)
    {
        //
    }
}
