<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateBidCarsImportApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredKey = (string) config('services.bid_cars.import_api_key', '');

        if ($configuredKey === '') {
            return response()->json(['message' => 'Bid.cars import API is not configured.'], 503);
        }

        $header = $request->header('Authorization', '');
        $providedKey = str_starts_with($header, 'Bearer ')
            ? substr($header, 7)
            : (string) $request->header('X-API-Key');

        if ($providedKey === '' || ! hash_equals($configuredKey, $providedKey)) {
            return response()->json(['message' => 'Invalid or missing API key.'], 401);
        }

        return $next($request);
    }
}