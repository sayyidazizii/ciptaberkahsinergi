<?php

namespace App\Http\Controllers\PPOB;

use Illuminate\Http\Request;
use App\Models\CoreAnouncement;
use App\Models\PreferenceCompany;
use Illuminate\Support\Facades\Log;

class AppController extends PPOBController
{
    public function getPreferenceCompany() {
       $preferencecompany = PreferenceCompany::first();
       if(auth('sanctum')->check()){
            return response()->json($preferencecompany);
       }else{
            return response()->json($preferencecompany->only(['company_name']));
       }
    }
    public function anouncement(){
        $announcement = CoreAnouncement::select(["title","message","image","type","created_at","link"])->active()->latest()->get()->map(function($item){
            $item->imageUrl = $item->image ? asset('storage/'.$item->image) : null;
            $item->url =  $item->link;
            return $item;
        });
        if($this->isSandbox()||auth()->user()->isDev()){
            $announcement->push([
                "title" => "Sandbox",
                "message" => "This is a sandbox message",
                "imageUrl" => "https://picsum.photos/100/100",
                "type" => "info",
                "created_at" => now(),
                "url" => "https://google.com"
            ]);
        }
        return response()->json([
            'title' => "Success",
            'error' => false,
            'status' => "success",
            'message' => "Data exists",
            'data' => $announcement,
            'url' => request()->fullUrl(),
        ]);
    }
}
