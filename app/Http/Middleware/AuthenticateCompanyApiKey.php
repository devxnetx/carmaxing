<?php

namespace App\Http\Middleware;

use App\Models\ApiRequestLog;
use App\Models\CompanyApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateCompanyApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');
        $plainKey = str_starts_with($header, 'Bearer ') ? substr($header, 7) : $request->header('X-API-Key');

        if (! $plainKey || ! str_starts_with($plainKey, 'ac_')) {
            return response()->json(['message' => 'Invalid or missing API key.'], 401);
        }

        $prefix = substr($plainKey, 0, 12);
        $apiKey = CompanyApiKey::query()
            ->with('company.user')
            ->where('key_prefix', $prefix)
            ->where('is_active', true)
            ->first();

        if (! $apiKey || ! $apiKey->matches($plainKey) || ! $apiKey->isValid()) {
            return response()->json(['message' => 'Invalid or expired API key.'], 401);
        }

        $apiKey->update(['last_used_at' => now()]);

        $request->attributes->set('company', $apiKey->company);
        $request->attributes->set('api_key', $apiKey);

        $response = $next($request);

        ApiRequestLog::query()->create([
            'company_api_key_id' => $apiKey->id,
            'method' => $request->method(),
            'path' => '/'.$request->path(),
            'status_code' => $response->getStatusCode(),
            'created_at' => now(),
        ]);

        return $response;
    }
}