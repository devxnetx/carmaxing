<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteNewsPost;
use App\Models\User;
use App\Services\CatalogCountService;
use App\Services\SiteNewsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SiteNewsController extends Controller
{
    public function index(CatalogCountService $catalogCounts): View
    {
        $posts = SiteNewsPost::query()
            ->with('sentBy')
            ->latest()
            ->paginate(20);

        $stats = [
            'news_subscribers' => $catalogCounts->newsSubscriberCount(),
            'news_non_subscribers' => $catalogCounts->newsNonSubscriberCount(),
            'price_digest' => User::query()->where('subscribe_price_digest', true)->whereNotNull('email')->count(),
            'new_listings_digest' => User::query()->where('subscribe_new_listings_digest', true)->whereNotNull('email')->count(),
        ];

        $subscribers = User::query()
            ->where('subscribe_news', true)
            ->whereNotNull('email')
            ->orderBy('name')
            ->paginate(25, ['*'], 'subscribers_page');

        return view('admin.site-news.index', compact('posts', 'stats', 'subscribers'));
    }

    public function store(Request $request, SiteNewsService $service): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:10000'],
            'recipient_target' => ['required', Rule::in(SiteNewsService::recipientTargets())],
        ]);

        $post = SiteNewsPost::query()->create([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'recipient_target' => $validated['recipient_target'],
            'sent_by_user_id' => $request->user()->id,
        ]);

        $sent = $service->send($post, $validated['recipient_target']);

        return redirect()
            ->route('admin.site-news.index')
            ->with('success', __('admin.site_news_sent', ['count' => $sent]));
    }
}