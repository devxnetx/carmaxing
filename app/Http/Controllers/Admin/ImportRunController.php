<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MobileBgImportRun;
use Illuminate\Http\RedirectResponse;
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

    public function cancel(MobileBgImportRun $import): RedirectResponse
    {
        abort_unless($import->isActive(), 404);

        $import->markAsFailed(__('admin.import_cancelled'));

        return back()->with('success', __('admin.import_cancelled_notice'));
    }
}