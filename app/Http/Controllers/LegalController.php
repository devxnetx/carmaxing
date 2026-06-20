<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class LegalController extends Controller
{
    public function privacy(): View
    {
        return view('legal.show', [
            'page' => 'privacy',
            'title' => __('legal.privacy.title'),
            'sections' => __('legal.privacy.sections'),
            'updated' => __('legal.privacy.updated'),
        ]);
    }

    public function terms(): View
    {
        return view('legal.show', [
            'page' => 'terms',
            'title' => __('legal.terms.title'),
            'sections' => __('legal.terms.sections'),
            'updated' => __('legal.terms.updated'),
        ]);
    }

    public function cookies(): View
    {
        return view('legal.cookies', [
            'title' => __('legal.cookies.title'),
            'updated' => __('legal.cookies.updated'),
            'intro' => __('legal.cookies.intro'),
            'categories' => __('legal.cookies.categories'),
            'inventory' => config('cookies.inventory', []),
        ]);
    }
}