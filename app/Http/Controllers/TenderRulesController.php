<?php

namespace App\Http\Controllers;

use App\Support\TenderRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenderRulesController extends Controller
{
    public function accept(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->forceFill([
            'tender_rules_accepted_at' => now(),
            'tender_rules_version' => TenderRules::version(),
        ])->save();

        return response()->json([
            'accepted' => true,
            'accepted_at' => $user->tender_rules_accepted_at->toIso8601String(),
        ]);
    }
}