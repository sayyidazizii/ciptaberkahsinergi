<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoreCompany extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

    protected $table        = 'core_company'; 
    protected $primaryKey   = 'company_id';
    
    protected $guarded = [
        'company_id',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
    ];

}
