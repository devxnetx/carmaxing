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

}