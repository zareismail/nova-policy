<?php

namespace Zareismail\NovaPolicy\Contracts;

interface Ownable
{
    /**
     * Indicate Model Authenticatable.
     *
     * @return mixed
     */
    public function owner();
}
