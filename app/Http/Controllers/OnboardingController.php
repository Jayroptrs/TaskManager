<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function complete(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasCompletedOnboarding()) {
            $user->forceFill([
                'onboarding_completed_at' => now(),
            ])->save();
        }

        return response()->json([
            'completed' => true,
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->user()->forceFill([
            'onboarding_completed_at' => null,
        ])->save();

        return back()->with('success', __('profile.onboarding_reset_success'));
    }
}
