<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BackupDataController extends Controller
{
    public function index(){
        return view('content.Backup.index');
    }

     public function store(){
        return true;
    }
}
