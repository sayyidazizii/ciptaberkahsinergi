<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoreAnouncement extends Model
{
    use HasFactory;
    protected $guarded=[];
    protected $fillable = [
        "title",
        "message",
        "link",
        "image",
        "type",
        "broadcast_type",
        "image_gallery",
        "broadcast_link",
        "is_active",
        "should_broadcast",
        "start_date",
        "end_date",
        "expires_at",
        "recuring",
        "recuring_type",
        "recuring_interval",
        "recuring_day",
    ];
    public function scopeActive($query): void
    {
        $query->where('start_date','<=',now())->where('end_date','>=',now());
    }
}
