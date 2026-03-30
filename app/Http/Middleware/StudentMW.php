<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StudentMW
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        
        $user = $request->user();

        if (!$user || !$user->isStudent()) {
            return response()->json(['message' => 'Only students allowed'], 403);
        }

        return $next($request);
    }
}
