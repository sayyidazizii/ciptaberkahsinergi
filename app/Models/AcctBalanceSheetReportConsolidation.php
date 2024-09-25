<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcctBalanceSheetReportConsolidation extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

    protected $table        = 'acct_balance_sheet_report_consolidation'; 
    protected $primaryKey   = 'balance_sheet_report_id';
    
    protected $guarded = [
        'balance_sheet_report_id',
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
