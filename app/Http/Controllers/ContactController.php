<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use App\Support\ContactCaptcha;
use App\Support\WebsiteManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function __construct(
        private ContactCaptcha $captcha,
    ) {}

    public function show(): View
    {
        return view('pages.contact', [
            'title' => __('pages.contact.title'),
            'intro' => __('pages.contact.intro'),
            'phone' => config('site.contact_phone'),
            'captcha' => $this->captcha->generate(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->filled('website')) {
            abort(422);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['nullable', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
            'captcha_answer' => ['required', 'integer'],
        ]);

        if (! $this->captcha->validate((string) $data['captcha_answer'])) {
            return back()
                ->withInput($request->except('captcha_answer', 'website'))
                ->withErrors(['captcha_answer' => __('pages.contact.captcha_invalid')]);
        }

        $message = ContactMessage::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'subject' => $data['subject'] ?? null,
            'message' => $data['message'],
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500) ?: null,
            'locale' => app()->getLocale(),
        ]);

        Mail::to(WebsiteManager::email())->send(new ContactMessageMail($message));

        return redirect()
            ->route('pages.contact')
            ->with('success', __('pages.contact.sent_success'));
    }
}