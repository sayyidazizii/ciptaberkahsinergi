<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WhatsappOtp extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $fillable = [
        "uuid",
        "otp_code",
        "member_id",
        "user_id",
        "created_id",
        "expired_at",
    ];
}
