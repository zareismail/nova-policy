<?php

namespace Zareismail\NovaPolicy;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class PermissionCollection extends Collection
{
    /**
     * Determine if the collection contains wildcard permission.
     *
     * @return bool
     */
    public function isWildcard(): bool
    {
        return $this->contains(function ($permission) {
            return $permission->name === Helper::WILD_CARD;
        });
    }

    /**
     * Determine if the collection contains wildcard ownable permission.
     *
     * @return bool
     */
    public function isWildcardOwnable(): bool
    {
        return $this->contains(function ($permission) {
            return $permission->name === Helper::WILD_CARD_OWNABLE;
        });
    }

    /**
     * Determine if the collection contains blocked permission.
     *
     * @return bool
     */
    public function isBlocked(): bool
    {
        return $this->contains(function ($permission) {
            return $permission->name === Helper::BLOCKED;
        });
    }

    /**
     * Determine if the collection just contains action permissions.
     *
     * @return bool
     */
    public function isAction(): bool
    {
        return $this->contains(function ($permission) {
            return in_array($permission->name, Helper::actions());
        });
    }

    /**
     * Determine if the collection just contains ownable permissions.
     *
     * @return bool
     */
    public function isOwnable(): bool
    {
        return $this->contains(function ($permission) {
            return  Str::startsWith($permission->name, Helper::OWNABLE);
        });
    }

    /**
     * Determine if the collection just contains wildcard partial permissions.
     *
     * @return bool
     */
    public function isPartial(): bool
    {
        return $this->contains(function ($permission) {
            return  Str::endsWith($permission->name, Helper::WILD_CARD);
        });
    }
}
