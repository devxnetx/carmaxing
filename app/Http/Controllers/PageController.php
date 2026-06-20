<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PageController extends Controller
{
    public function about(): View
    {
        return view('pages.about', [
            'title' => __('pages.about.title'),
            'sections' => __('pages.about.sections'),
        ]);
    }

    public function contact(): View
    {
        return view('pages.contact', [
            'title' => __('pages.contact.title'),
            'intro' => __('pages.contact.intro'),
            'email' => config('site.contact_email', config('mail.from.address')),
            'phone' => config('site.contact_phone'),
        ]);
    }
}