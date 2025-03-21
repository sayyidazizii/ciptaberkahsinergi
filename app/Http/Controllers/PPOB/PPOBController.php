<?php

namespace App\Http\Controllers\PPOB;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PPOBController extends Controller
{
    public $globalMaintenance=0;
    public $maintenanceReg=0;
    public function journal() {
        return view('Page.Journal.ListJournal');
    }
    public function addJournal(){
        return view('Page.Journal.AddListJournal');
    }
    public function storeJournal(Request $request) {
        // content
    }
    public function isMaintenaceRegister() {
        return env("MAINTENANCE_REGISTER", false);
    }

    protected function isSandbox() {
        return request()->hasHeader('sandbox')||request()->has('sandbox');
    }
}
