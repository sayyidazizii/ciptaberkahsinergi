<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MigrationProfitLoss extends Model
{
    use HasFactory;

    protected $table =  'migration_acct_profit_loss_report'; 

    /**
     * The attributes that are mass assignable.
     *  
     * @var array
     */
    protected $fillable = [
        'profit_loss_report_id',
        'format_id',
        'report_no',
        'account_type_id',
        'account_id',
        'account_code',
        'account_name',
        'account_amount_migration',
        'report_formula',
        'report_operator',
        'report_type',
        'report_tab',
        'report_bold',
        'created_id',
        'created_at',
        'updated_at',
        'deleted_at',
        'data_state',
    ];
    
        
}
