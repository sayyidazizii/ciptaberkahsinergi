<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PPOBSetting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

     protected $table        = 'preference_ppob'; 
     protected $primaryKey   = 'id';
     
     protected $guarded = [
         'id',
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
