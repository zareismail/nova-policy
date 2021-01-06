<?php 

namespace Zareismail\NovaPolicy;
 
use Illuminate\Contracts\Auth\Authenticatable;
 
trait InteractsWithUser  
{      
 
    /**
     * Bootstrap the model and its traits.
     *
     * @return void
     */
    protected static function bootInteractsWithUser()
    { 
        static::saved(function($model) { 
            $model->flushCache(); 
        }); 

        static::deleting(function($model) { 
            $model->flushCache(); 
        }); 
    } 

    /**
     * Query the related user.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function user()
    {
        return $this->morphTo();
    }

    /**
     * Restrict the query on the user.
     * 
     * @param  \Illuminate\Database\Eloquent\Builder $query 
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user  
     * @return \Illuminate\Database\Eloquent\Builder                 
     */
    public function scopeAuthenticated($query, Authenticatable $user)
    {
        return $query->whereHas('user', function($query) use ($user) {
            $query->whereKey(optional($user)->id);
        });
    }
    /**
     * Flush the cahe permission of the user.
     * 
     * @return $this
     */
    public function flushCache()
    {
        app(Contracts\Repository::class)->review($this->user ?? $this->user()->firstONew());

        return $this;
    }
}
