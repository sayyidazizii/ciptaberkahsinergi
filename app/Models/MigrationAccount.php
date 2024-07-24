<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MigrationAccount extends Model
{
    use HasFactory;

    protected $table =  'migration_acct_account'; 

    /**
     * The attributes that are mass assignable.
     *  
     * @var array
     */
    protected $fillable = [
            'account_id',

            'branch_id',

            'account_type_id',

            'account_code',

            'account_name',

            'account_group',

            'account_suspended',

            'parent_account_id',

            'top_parent_account_id',

            'account_has_child',

            'opening_debit_balance',

            'opening_credit_balance',

            'debit_change',

            'credit_change',

            'account_default_status',

            'account_remark',

            'account_status',

            'created_id',

            'created_at',

            'updated_id',

            'updated_at',

            'deleted_id',

            'deleted_at',

            'data_state',

    ];

        
}
