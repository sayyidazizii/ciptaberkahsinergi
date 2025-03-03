<?php

namespace App\Http\Controllers\PPOB;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class reportBugController extends Controller
{
    //
    public function post(Request $request) {
        // content
        $response=[];
        $response['error'] 						= FALSE;
        $response['error_msg_title'] 			= "Success";
        $response['error_msg'] 					= "OK";
        $response['data']    = 'Terimakasih atas masukan anda';
        $request->validate(['feedback'=>'required','title'=>'required']);
        $user = auth()->id();
        Log::warning("['title'=>'{$request->title}','feedback'=>'{$request->feedback}','user'=>{$user}]");
        return response($response);
    }
}
