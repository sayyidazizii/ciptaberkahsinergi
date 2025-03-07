<?php

namespace App\Http\Controllers\PPOB;

use Illuminate\Http\Request;

class PPOBController extends Controller
{
    public function journal() {
        return view('Page.Journal.ListJournal');
    }
    public function addJournal(){
        return view('Page.Journal.AddListJournal');
    }
    public function storeJournal(Request $request) {
        // content
    }
}
