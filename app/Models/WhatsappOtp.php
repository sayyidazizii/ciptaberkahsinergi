<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WhatsappOtp extends Model
{
    use HasFactory,Prunable;
    protected $guarded = [];
    protected $fillable = [
        "uuid",
        "otp_code",
        "member_id",
        "user_id",
        "created_id",
        "expired_at",
    ];
    public function prunable()
    {
        return static::where('created_at', '<=', now()->subDays(5));
    }
}
