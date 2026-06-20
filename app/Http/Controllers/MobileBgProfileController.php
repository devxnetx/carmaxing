<?php

namespace App\Http\Controllers;

use App\Services\MobileBg\MobileBgProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MobileBgProfileController extends Controller
{
    public function extract(Request $request, MobileBgProfileService $service): RedirectResponse
    {
        $company = $request->user()->company;
        abort_unless($company, 403);

        $data = $request->validate([
            'mobile_bg_profile_url' => ['required', 'string', 'max:255'],
        ]);

        try {
            $service->extractAndApply($company, $data['mobile_bg_profile_url']);
        } catch (\InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        } catch (\Throwable) {
            return back()
                ->withInput()
                ->with('error', __('messages.mobile_bg_profile_extract_failed'));
        }

        return redirect()
            ->route('company.edit')
            ->with('success', __('messages.mobile_bg_profile_extracted'));
    }
}