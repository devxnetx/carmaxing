<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->with(['company', 'roles'])->withCount('listings');

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($type = $request->string('type')->toString()) {
            $query->where('account_type', $type);
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user): View
    {
        $user->load(['company.region', 'socialAccounts', 'roles'])
            ->loadCount(['listings', 'favorites', 'savedSearches']);

        $listings = $user->listings()
            ->with(['brand', 'model.parent'])
            ->latest('updated_at')
            ->limit(10)
            ->get();

        return view('admin.users.show', compact('user', 'listings'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === $request->user()->id && $request->boolean('is_admin') === false, 422);

        $data = $request->validate([
            'is_admin' => ['boolean'],
        ]);

        if (array_key_exists('is_admin', $data)) {
            $user->syncAdminRole($request->boolean('is_admin'));
        }

        return back()->with('success', __('admin.user_updated'));
    }
}