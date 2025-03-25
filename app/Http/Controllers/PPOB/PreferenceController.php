<?php

namespace App\Http\Controllers\PPOB;

use Illuminate\Http\Request;
use App\Models\PreferenceCompany;
use Illuminate\Support\Facades\Log;

class PreferenceController extends PPOBController
{
    public function getPreferenceCompany() {
       $preferencecompany = PreferenceCompany::first();
       if(auth('sanctum')->check()){
            return response()->json($preferencecompany);
       }else{
            return response()->json($preferencecompany->only(['company_name']));
       }
    }
}
