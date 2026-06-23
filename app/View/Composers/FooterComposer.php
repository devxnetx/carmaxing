<?php

namespace App\View\Composers;

use App\Support\CatalogCache;
use Illuminate\View\View;

class FooterComposer
{
    public function compose(View $view): void
    {
        $view->with('footerPopularBrands', CatalogCache::popularBrands());
    }
}