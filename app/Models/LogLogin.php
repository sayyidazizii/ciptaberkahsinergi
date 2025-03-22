<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogLogin extends Model
{
    use HasFactory;
    // protected $table = 'log_logins';
    protected $guarded = [];
    protected $fillable = [
        'user_id',
        'member_id',
        'member_no',
        'log_state',
        'block_state',
        'imei',
        'log_change_password_status',
        'log_login_remark',
        'type',
        'created_on',
        'last_update',
        'created_at',
        'updated_at',
    ];
}
