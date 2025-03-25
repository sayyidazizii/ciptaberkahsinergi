<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class LogRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::info($request->except(['password','password_transaksi']),[
            "Method" =>$request->method(),
            "Full Url" =>$request->fullUrl(),
            "IP Address" =>$request->ip(),
            "User Agent" =>$request->userAgent(),
            "User" =>$request->user()??"-",
            "Header" =>$request->header(),
        ]);
        return $next($request);
    }
}
