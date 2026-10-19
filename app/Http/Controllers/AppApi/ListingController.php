<?php

namespace App\Http\Controllers\AppApi;

use App\Enums\ListingStatus;
use App\Http\Controllers\Controller;
use App\Mail\ListingInquiryMail;
use App\Models\Listing;
use App\Models\ListingInquiry;
use App\Models\ListingReport;
use App\Services\ListingSearchService;
use App\Support\AppApi\ListingPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;

class ListingController extends Controller
{
    public function __construct(
        private ListingSearchService $search,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 20), 1), 50);
        $page = $this->search->search($request, $perPage);
        $ids = $page->getCollection()->pluck('id')->all();

        return response()->json(ListingPayload::page($page, $this->favoritedIds($ids)));
    }

    public function show(string $id): JsonResponse
    {
        $listing = $this->publishedListing($id, [
            'brand',
            'model.parent',
            'region',
            'images',
            'user',
            'features.category',
            'priceChanges',
            'company' => fn ($query) => $query->withCount([
                'listings as listings_count' => fn ($listings) => $listings->where('status', ListingStatus::Published),
            ]),
        ]);

        $viewer = auth('sanctum')->user();
        if (! $viewer || $viewer->id !== $listing->user_id) {
            $listing->increment('views_count');
        }

        $favorited = $this->favoritedIds([$listing->id]) !== [];

        return response()->json([
            'data' => ListingPayload::detail(
                $listing,
                $favorited,
                $this->relatedCards($listing, 'similar'),
                $listing->company_id ? $this->relatedCards($listing, 'dealer') : [],
            ),
        ]);
    }

    public function phoneClick(string $id): Response
    {
        $this->publishedListing($id, [])->increment('phone_clicks_count');

        return response()->noContent();
    }

    public function report(Request $request, string $id): JsonResponse
    {
        $listing = $this->publishedListing($id, []);
        $data = $request->validate([
            'reason' => ['required', 'in:scam,wrong_info,duplicate,other'],
            'details' => ['nullable', 'string', 'max:2000'],
        ]);

        ListingReport::query()->create([
            'listing_id' => $listing->id,
            'user_id' => auth('sanctum')->user()?->id,
            'reason' => $data['reason'],
            'details' => $data['details'] ?? null,
        ]);

        return response()->json(['message' => __('messages.report_submitted')]);
    }

    public function contact(Request $request, string $id): JsonResponse
    {
        $listing = $this->publishedListing($id, ['company', 'user']);
        abort_if($request->user()->id === $listing->user_id, 403);

        $data = $request->validate([
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $sellerEmail = $listing->sellerEmail();
        if (! $sellerEmail) {
            return response()->json([
                'message' => __('messages.inquiry_no_seller_email'),
            ], 422);
        }

        Mail::to($sellerEmail)->send(new ListingInquiryMail(
            $listing,
            $request->user(),
            $data['message'],
        ));

        ListingInquiry::query()->create([
            'listing_id' => $listing->id,
            'user_id' => $request->user()->id,
            'message' => $data['message'],
            'created_at' => now(),
        ]);
        $listing->increment('inquiries_count');

        return response()->json(['message' => __('messages.inquiry_sent')]);
    }

    /** @param  list<int>  $ids */
    private function favoritedIds(array $ids): array
    {
        $user = auth('sanctum')->user();

        if (! $user || $ids === []) {
            return [];
        }

        return $user->favorites()
            ->whereIn('listing_id', $ids)
            ->pluck('listing_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** @return list<array<string, mixed>> */
    private function relatedCards(Listing $listing, string $kind): array
    {
        $query = Listing::query()
            ->published()
            ->where('id', '!=', $listing->id)
            ->with(['brand', 'model', 'images', 'region'])
            ->latest('published_at')
            ->limit(4);

        if ($kind === 'dealer') {
            $query->where('company_id', $listing->company_id);
        } else {
            $query->where('brand_id', $listing->brand_id);
        }

        $items = $query->get();
        $favorited = array_fill_keys($this->favoritedIds($items->pluck('id')->all()), true);

        return $items
            ->map(fn (Listing $item) => ListingPayload::card($item, isset($favorited[$item->id])))
            ->values()
            ->all();
    }

    /** @param  array<int|string, mixed>  $with */
    private function publishedListing(string $id, array $with): Listing
    {
        abort_unless(ctype_digit($id), 404);

        return Listing::query()
            ->published()
            ->with($with)
            ->findOrFail($id);
    }
}
