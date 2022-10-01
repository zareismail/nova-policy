<?php

namespace Zareismail\NovaPolicy;

use Illuminate\Contracts\Auth\Authenticatable;
use Zareismail\NovaPolicy\Contracts\Repository as RepositoryContracts;

class Repository implements RepositoryContracts
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $app;

    /**
     * The cached permissions in the memory.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected static $cachedPermissions = [];

    /**
     * Create a new authenticator instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Determine the user that should be authenticate.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return $this
     */
    public function for(Authenticatable $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get the user instance.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return $this
     */
    public function getUser()
    {
        return $this->user ?? $this->app['request']->user();
    }

    /**
     * Determine if the given permission should be granted for the given user.
     *
     * @param  string  $permission
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function has($permission, $arguments = []): bool
    {
        $cacheKey = $this->cacheKey($this->getUser());

        if (! isset(static::$cachedPermissions[$cacheKey])) {
            static::$cachedPermissions[$cacheKey] = $this->permissions();
        }

        return collect(static::$cachedPermissions[$cacheKey])->contains($permission);
    }

    /**
     * Get the available user permissions.
     *
     * @return array
     */
    public function permissions(): array
    {
        return $this->app['cache']->sear($this->cacheKey($this->getUser()), function () {
            return array_unique(array_merge(
                $this->userRolesPermissions(), $this->userPermissions()
            ));
        });
    }

    /**
     * Get the user staright permissions.
     *
     * @return array
     */
    public function userPermissions(): array
    {
        return PolicyUserPermission::authenticated($this->getUser())->get()->map->name->values()->all();
    }

    /**
     * Get the user permissions via roles.
     *
     * @return array
     */
    public function userRolesPermissions(): array
    {
        return PolicyUserRole::with('role.permissions')->authenticated($this->getUser())->get()
                    ->map->role->filter()->values()
                    ->flatMap->permissions->filter()->values()
                    ->map->name->unique()->values()->all();
    }

    /**
     * Forget the cached values for the given user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return $this
     */
    public function review(Authenticatable $user)
    {
        $this->app['cache']->forget($this->cacheKey($user));

        return $this;
    }

    /**
     * Get the string cache key for the given user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return string
     */
    public function cacheKey(Authenticatable $user): string
    {
        return md5(get_called_class().get_class($user).$user->getAuthIdentifier());
    }
}
