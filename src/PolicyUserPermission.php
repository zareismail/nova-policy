<?php 

namespace Zareismail\NovaPolicy;

use Illuminate\Database\Eloquent\Relations\MorphPivot; 
 
class PolicyUserPermission extends MorphPivot
{    
    use InteractsWithUser;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'policy_user_permission'; 
}