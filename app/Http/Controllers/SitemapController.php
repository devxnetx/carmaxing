<?php

namespace App\Http\Controllers;

use App\Support\SitemapBuilder;
use Illuminate\Http\Response;
use Illuminate\View\View;

class SitemapController extends Controller
{
    public function xml(SitemapBuilder $builder): Response
    {
        $urls = $builder->xmlUrls();

        return response()->view('sitemap.xml', compact('urls'), 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    public function html(SitemapBuilder $builder): View
    {
        return view('sitemap.html', [
            'sections' => $builder->htmlSections(),
            'xmlUrl' => route('sitemap'),
        ]);
    }
}