<?php

namespace App\View\Composers;

use App\Models\VehicleBrand;
use Illuminate\View\View;

class FooterComposer
{
    public function compose(View $view): void
    {
        $view->with('footerPopularBrands', VehicleBrand::query()
            ->where('is_popular', true)
            ->orderBy('sort_order')
            ->get());
    }
}