<?php

namespace Zareismail\NovaPolicy\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface Repository
{
    /**
     * Determine the user that should be authenticate.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return $this
     */
    public function for(Authenticatable $user);

    /**
     * Determine if the given ability should be granted for the given user.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function has($ability, $arguments = []): bool;

    /**
     * Forget the cached values for the given user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return $this
     */
    public function review(Authenticatable $user);
}
