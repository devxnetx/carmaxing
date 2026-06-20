<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MobileBgImportRun;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImportRunController extends Controller
{
    public function index(Request $request): View
    {
        $query = MobileBgImportRun::query()
            ->with('company')
            ->latest();

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        $imports = $query->paginate(20)->withQueryString();

        return view('admin.imports.index', compact('imports'));
    }
}