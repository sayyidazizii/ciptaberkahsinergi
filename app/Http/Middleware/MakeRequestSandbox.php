<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MakeRequestSandbox
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->header('sandbox', true);
        $request = $request->merge(['sandbox' => true]);
        $response = $next($request);
        // $response = $response->header('sandbox', true);
        return $response;
    }
}
