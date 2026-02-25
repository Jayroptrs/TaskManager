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
        ]);
    }

    public function store(Request $request)
    {
        $isGuest = !$request->user();
        $recaptchaEnabled = $isGuest && config('services.recaptcha.enabled');

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

        $validated = $request->validate([...$baseRules, ...$guestRules, ...$captchaRules]);

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
                    'g-recaptcha-response' => 'reCAPTCHA verificatie mislukt. Probeer opnieuw.',
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

        return back()->with('success', 'Je supportbericht is verzonden.');
    }
}
