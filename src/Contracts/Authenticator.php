<?php

namespace Zareismail\NovaPolicy\Contracts;

interface Authenticator
{
    /**
     * Determine if the given ability should be granted for the given user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return \Illuminate\Auth\Access\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorize($user, $ability, $arguments = []);
}
