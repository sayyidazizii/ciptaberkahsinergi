<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Documentation extends Model
{
   // use HasFactory;
   protected $connection = 'mysql3';
   protected $table = 'ppob_api_route'; 
   protected $primaryKey = 'id';
   protected $guarded = ['id', 'created_at'];
}
