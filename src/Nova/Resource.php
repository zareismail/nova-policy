<?php 

namespace Zareismail\NovaPolicy\Nova;

use Laravel\Nova\Resource as NovaResource; 
use Illuminate\Http\Request;        

abstract class Resource extends NovaResource
{  
    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'Nova Policy';

	/**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
    	"name"
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    abstract public function fields(Request $request); 
}