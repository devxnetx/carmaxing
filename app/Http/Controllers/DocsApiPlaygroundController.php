<?php

namespace App\Http\Controllers;

use App\Models\CompanyApiKey;
use App\Models\Listing;
use App\Support\ApiDocsPhpExample;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class DocsApiPlaygroundController extends Controller
{
    private const ALLOWED_PATHS = [
        '/catalog',
        '/listings',
        '/listings/{id}',
    ];

    public function run(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && $user->isCompany() && $user->company, Response::HTTP_FORBIDDEN);

        $company = $user->company;
        abort_unless($company->apiKeys()->where('is_active', true)->exists(), Response::HTTP_FORBIDDEN);

        $data = $request->validate([
            'api_key' => ['required', 'string', 'starts_with:ac_'],
            'method' => ['required', 'string', Rule::in(['GET', 'POST', 'PUT'])],
            'path' => ['required', 'string', Rule::in(self::ALLOWED_PATHS)],
            'listing_id' => ['nullable', 'string', 'max:100'],
            'body' => ['nullable', 'array'],
            'query' => ['nullable', 'array'],
        ]);

        $apiKeyModel = CompanyApiKey::query()
            ->where('company_id', $company->id)
            ->where('key_prefix', substr($data['api_key'], 0, 12))
            ->where('is_active', true)
            ->first();

        abort_unless($apiKeyModel && $apiKeyModel->matches($data['api_key']), Response::HTTP_FORBIDDEN);

        $resolvedPath = $data['path'] === '/listings/{id}'
            ? '/listings/'.$this->resolveListingId($company->id, $data['listing_id'] ?? null)
            : $data['path'];

        $body = $data['body'] ?? null;
        $query = $data['query'] ?? null;

        if ($data['method'] === 'POST' && is_array($body) && isset($body['external_id'])) {
            $body['external_id'] = $body['external_id'].'-'.now()->timestamp;
        }

        $fullUrl = rtrim(config('api.base_url'), '/').$resolvedPath;
        $httpResponse = $this->sendRequest(
            $data['method'],
            $resolvedPath,
            $data['api_key'],
            $body,
            $query,
        );

        $decoded = json_decode($httpResponse->getContent(), true);
        if (! is_array($decoded)) {
            $decoded = ['raw' => $httpResponse->getContent()];
        }

        return response()->json([
            'status' => $httpResponse->getStatusCode(),
            'body' => $decoded,
            'php_example' => ApiDocsPhpExample::build(
                $data['method'],
                $fullUrl,
                $body,
                $query,
                $data['api_key'],
            ),
        ], $httpResponse->isSuccessful() ? Response::HTTP_OK : $httpResponse->getStatusCode());
    }

    public function sampleListingId(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && $user->isCompany() && $user->company, Response::HTTP_FORBIDDEN);
        abort_unless($user->company->apiKeys()->where('is_active', true)->exists(), Response::HTTP_FORBIDDEN);

        $listing = Listing::query()
            ->where('company_id', $user->company->id)
            ->latest('id')
            ->first();

        return response()->json([
            'listing_id' => $listing ? (string) ($listing->ad_number ?? $listing->id) : null,
        ]);
    }

    private function sendRequest(string $method, string $resolvedPath, string $apiKey, ?array $body, ?array $query): Response
    {
        $url = '/api/v1'.$resolvedPath;

        if ($query) {
            $url .= '?'.http_build_query($query);
        }

        $content = in_array(strtoupper($method), ['POST', 'PUT'], true) && $body
            ? json_encode($body)
            : null;

        $apiRequest = Request::create(
            $url,
            strtoupper($method),
            $body ?? [],
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer '.$apiKey,
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ],
            $content,
        );

        $kernel = app(Kernel::class);
        $response = $kernel->handle($apiRequest);
        $kernel->terminate($apiRequest, $response);

        return $response;
    }

    private function resolveListingId(int $companyId, ?string $listingId): string
    {
        if ($listingId) {
            return $listingId;
        }

        $listing = Listing::query()
            ->where('company_id', $companyId)
            ->latest('id')
            ->first();

        if (! $listing) {
            abort(response()->json([
                'message' => __('api_docs.playground_no_listing'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        return (string) ($listing->ad_number ?? $listing->id);
    }
}