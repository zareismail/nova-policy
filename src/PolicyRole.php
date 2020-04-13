<?php 

namespace Zareismail\NovaPolicy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
 
class PolicyRole extends Model
{ 
	use SoftDeletes;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

	public function permissions()
	{
		return $this->belongsToMany(PolicyPermission::class, 'policy_permission_role');
	}
}