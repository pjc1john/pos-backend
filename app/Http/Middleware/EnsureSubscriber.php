<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriber
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->subscriber_id) {
            return response()->json(['message' => 'User has no subscriber assigned'], 403);
        }

        return $next($request);
    }
}
