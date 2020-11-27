<?php 

namespace Zareismail\NovaPolicy;

use Illuminate\Database\Eloquent\Relations\MorphPivot; 
 
class PolicyUserRole extends MorphPivot
{    
    use InteractsWithUser;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'policy_user_role';  

    public function role()
    {
    	return $this->belongsTo(PolicyRole::class, 'policy_role_id');
    }
}