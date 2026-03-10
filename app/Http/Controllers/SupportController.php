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
        $activeTicketId = null;

        if ($request->user()) {
            $activeTicketId = $request->integer('ticket') ?: null;

            $myMessages = $request->user()
                ->supportMessages()
                ->where('status', '!=', SupportMessage::STATUS_RESOLVED)
                ->with(['replies.user:id,name,email'])
                ->latest()
                ->take(10)
                ->get();

            if ($activeTicketId) {
                $activeTicket = $request->user()
                    ->supportMessages()
                    ->where('status', '!=', SupportMessage::STATUS_RESOLVED)
                    ->with(['replies.user:id,name,email'])
                    ->whereKey($activeTicketId)
                    ->first();

                if ($activeTicket && ! $myMessages->contains('id', $activeTicket->id)) {
                    $myMessages->prepend($activeTicket);
                }

                if ($activeTicket) {
                    $request->user()
                        ->unreadSupportReplies()
                        ->where('support_message_replies.support_message_id', $activeTicket->id)
                        ->update(['support_message_replies.read_at' => now()]);
                }
            }
        }

        return view('pages.support', [
            'myMessages' => $myMessages,
            'recaptchaEnabledForGuest' => $this->recaptchaEnabledFor($request),
            'activeTicketId' => $activeTicketId,
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
            'status' => SupportMessage::STATUS_OPEN,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
        ]);

        return back()->with('success', __('messages.support_sent'));
    }

    public function reply(Request $request, SupportMessage $supportMessage)
    {
        $user = $request->user();

        abort_unless($user && $supportMessage->user_id === $user->id, 403);

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:5', 'max:5000'],
        ]);

        $supportMessage->replies()->create([
            'user_id' => $user->id,
            'is_admin' => false,
            'message' => $validated['message'],
        ]);

        $nextStatus = $supportMessage->status === SupportMessage::STATUS_RESOLVED
            ? SupportMessage::STATUS_IN_PROGRESS
            : ($supportMessage->status === SupportMessage::STATUS_WAITING_FOR_USER
                ? SupportMessage::STATUS_IN_PROGRESS
                : $supportMessage->status);

        if ($nextStatus !== $supportMessage->status) {
            $supportMessage->update([
                'status' => $nextStatus,
                'resolved_at' => null,
                'admin_resolved_at' => null,
                'user_resolved_at' => null,
            ]);
        }

        return redirect()->route('support')->with('success', __('messages.support_reply_sent'));
    }

    public function resolve(Request $request, SupportMessage $supportMessage)
    {
        $user = $request->user();

        abort_unless($user && $supportMessage->user_id === $user->id, 403);
        abort_unless($supportMessage->admin_resolved_at !== null, 422);

        if ($supportMessage->user_resolved_at === null) {
            $supportMessage->update([
                'user_resolved_at' => now(),
                'status' => SupportMessage::STATUS_RESOLVED,
                'resolved_at' => now(),
            ]);
        }

        return redirect()->route('support')->with('success', __('messages.support_user_marked_resolved'));
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
