<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RejectBlockedUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(!empty($request->user())){
            if(empty($request->user()->member_imei)&&!$request->user()->isDev()){
                $id = auth()->id();
                Log::warning("User {$id} imei is empty | {$request->ip()}");
                activity()->log("User {$id} imei is empty | {$request->ip()}");
                auth()->user()->tokens()->delete();
                // abort_if($version!=$user->system_version,401);
                return response()->json([
                    'message' => 'Harap Registrasi Ulang Aplikasi!',
                    'otp_status' => 0,
                    "block_state"=>0,
                    'data' => null,
                    'token' => null
                ], 401);
            }
            if($request->user()->isBlocked()){
                $id = auth()->id();
                Log::warning("User {$id} is blocked | {$request->ip()}");
                activity()->log("User {$id} is blocked | {$request->ip()}");
                auth()->user()->tokens()->delete();
                // abort_if($version!=$user->system_version,401);
                return response()->json([
                    'message' => 'User Blocked! Contact Admin for Further Information!',
                    'otp_status' => 0,
                    "block_state"=>1,
                    'data' => null,
                    'token' => null
                ], 401);
                // abort(401);
            }
        }
        return $next($request);
    }
}
