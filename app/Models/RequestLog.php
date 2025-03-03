<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;


class RequestLog extends Model
{
    use HasFactory;
    use Prunable;
    protected $table        = 'request_log'; 
    protected $primaryKey   = 'id';
    
    protected $guarded = [
        'created_at',
        'updated_at',
    ];
    public function prunable()
    {
        return static::where('created_at', '<=', now()->subDays(2));
    }
    protected function pruning()
    {
        if(!Storage::exist('logs/requestLog-'.Carbon::now()->format("Y-m-d").'.json')){
            Storage::put('logs/requestLog-'.Carbon::now()->format("Y-m-d").'.json', $this->where('created_at', '<=', now()->subDays(2))->get());
        }
    }
}
