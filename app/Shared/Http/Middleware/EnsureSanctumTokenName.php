<?php

namespace App\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSanctumTokenName
{
    public function handle(Request $request, Closure $next, string $tokenName): Response
    {
        $token = $request->user()?->currentAccessToken();

        if ($token?->name !== $tokenName) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthenticated.',
                'errors'  => [],
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
