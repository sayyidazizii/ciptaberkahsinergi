<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PPOBTopUp extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

     protected $table        = 'ppob_topup'; 
     protected $primaryKey   = 'ppob_topup_id';
     
     protected $guarded = [
         'ppob_topup_id',
         'created_at',
         'updated_at',
     ];
     
     public function branch() {
        return $this->belongsTo(CoreBranch::class,'branch_id','branch_id');
    }
    public function account() {
        return $this->belongsTo(AcctAccount::class,'account_id','account_id');
    }

     /**
      * The attributes that should be hidden for serialization.
      *
      * @var array
      */
     protected $hidden = [
     ];
}
