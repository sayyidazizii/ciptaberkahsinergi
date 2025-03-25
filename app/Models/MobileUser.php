<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Core\Traits\SpatieLogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class MobileUser extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use SpatieLogsActivity;
    use HasRoles;
    protected $table = 'system_mobile_user';
    protected $primaryKey = 'user_id';
    protected $fillable = [
        "member_id",
        "branch_id",
        "uuid",
        "member_no",
        "password",
        "password_transaksi",
        "member_name",
        "member_phone",
        "member_imei",
        "member_user_status",
        "member_token",
        "log_state",
        "block_state",
        "otp_state",
        "expired_on",
        "username",
        "email",
        "system_version",
        "email_verified_at",
        "avatar",
        "google_id",
        "google_avatar",
        "google_avatar_original",
        "remember_token",
        "created_id",
        "updated_id",
        "deleted_id",
    ];
    protected $hidden = [
        'password',
        'password_transaksi',
        'remember_token',
        'created_on',
        'last_update',
    ];

    private $adminId = [1, 2];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    public function isAdministrator() {
        return ($this->user_id===2||$this->user_id===1);
    }
    public function isDev() {
        return in_array($this->user_id, $this->adminId);
    }
    public function isDeveloper() {
        return ($this->user_id===2||$this->user_id===1);
    }
    public function isAdmin() {
        return ($this->user_id===2||$this->user_id===1);
    }
    public function isBlocked() {
        return $this->block_state!=0;
    }
    public function member() {
       return $this->belongsTo(CoreMember::class,'member_id','member_id');
    }

    /**
     * Specifies the user's FCM token
     *
     * @return string|array
     */
    public function routeNotificationForFcm()
    {
        return $this->fcm_token;
    }
}
