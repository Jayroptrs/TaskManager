<?php

namespace App\Http\Controllers;

use App\Models\SupportMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class SupportController extends Controller
{
    public function index(Request $request)
    {
        $myMessages = collect();

        if ($request->user()) {
            $myMessages = $request->user()
                ->supportMessages()
                ->latest()
                ->take(5)
                ->get();
        }

        return view('pages.support', [
            'myMessages' => $myMessages,
            'recaptchaEnabledForGuest' => $this->recaptchaEnabledFor($request),
        ]);
    }

    public function store(Request $request)
    {
        $recaptchaEnabled = $this->recaptchaEnabledFor($request);

        $baseRules = [
            'subject' => ['required', 'string', 'min:5', 'max:120'],
            'category' => ['required', 'in:algemeen,account,bug,privacy,security,billing'],
            'message' => ['required', 'string', 'min:20', 'max:5000'],
        ];

        $guestRules = $request->user()
            ? []
            : [
                'guest_name' => ['required', 'string', 'min:2', 'max:120'],
                'guest_email' => ['required', 'email', 'max:255'],
            ];

        $captchaRules = $recaptchaEnabled
            ? ['g-recaptcha-response' => ['required', 'string']]
            : [];

        $validated = $request->validate(
            [...$baseRules, ...$guestRules, ...$captchaRules],
            ['g-recaptcha-response.required' => __('messages.recaptcha_required')]
        );

        if ($recaptchaEnabled) {
            $verifyResponse = Http::asForm()
                ->timeout(8)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => config('services.recaptcha.secret_key'),
                    'response' => $validated['g-recaptcha-response'],
                    'remoteip' => $request->ip(),
                ]);

            if (!$verifyResponse->ok() || !$verifyResponse->json('success')) {
                throw ValidationException::withMessages([
                    'g-recaptcha-response' => __('messages.recaptcha_failed'),
                ]);
            }
        }

        SupportMessage::query()->create([
            'user_id' => $request->user()?->id,
            'guest_name' => $validated['guest_name'] ?? null,
            'guest_email' => $validated['guest_email'] ?? null,
            'subject' => $validated['subject'],
            'category' => $validated['category'],
            'message' => $validated['message'],
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
        ]);

        return back()->with('success', __('messages.support_sent'));
    }

    private function recaptchaEnabledFor(Request $request): bool
    {
        if ($request->user()) {
            return false;
        }

        if (! (bool) config('services.recaptcha.enabled')) {
            return false;
        }

        if (! filled(config('services.recaptcha.site_key')) || ! filled(config('services.recaptcha.secret_key'))) {
            return false;
        }

        return true;
    }
}
