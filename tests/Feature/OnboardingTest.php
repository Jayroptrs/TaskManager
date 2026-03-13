<?php

use App\Models\User;
use Illuminate\Support\Carbon;

test('new user sees onboarding modal in app layout', function () {
    $user = User::factory()->create([
        'onboarding_completed_at' => null,
    ]);

    $this->actingAs($user)
        ->get(route('task.index'))
        ->assertOk()
        ->assertSee('data-onboarding-modal', false);
});

test('user who completed onboarding no longer sees onboarding modal', function () {
    $user = User::factory()->create([
        'onboarding_completed_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('task.index'))
        ->assertOk()
        ->assertDontSee('data-onboarding-modal', false);
});

test('authenticated user can complete onboarding', function () {
    $user = User::factory()->create([
        'onboarding_completed_at' => null,
    ]);

    $this->actingAs($user)
        ->postJson(route('onboarding.complete'))
        ->assertOk()
        ->assertJson([
            'completed' => true,
        ]);

    expect($user->fresh()->onboarding_completed_at)->not()->toBeNull();
});

test('guest cannot complete onboarding endpoint', function () {
    $this->post(route('onboarding.complete'))
        ->assertRedirect(route('login'));
});

test('authenticated user can reset onboarding from profile flow', function () {
    $user = User::factory()->create([
        'onboarding_completed_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('onboarding.reset'))
        ->assertRedirect();

    expect($user->fresh()->onboarding_completed_at)->toBeNull();
});

test('guest cannot reset onboarding endpoint', function () {
    $this->post(route('onboarding.reset'))
        ->assertRedirect(route('login'));
});

test('onboarding modal includes updated dashboard step copy for new users', function () {
    $user = User::factory()->create([
        'onboarding_completed_at' => null,
    ]);

    $this->actingAs($user)
        ->get(route('task.index'))
        ->assertOk()
        ->assertSee(__('ui.onboarding_step_2_title'))
        ->assertSee(__('ui.onboarding_step_3_copy'));
});

test('completing onboarding twice keeps first completion timestamp', function () {
    Carbon::setTestNow('2026-03-10 10:00:00');

    $user = User::factory()->create([
        'onboarding_completed_at' => null,
    ]);

    $this->actingAs($user)
        ->postJson(route('onboarding.complete'))
        ->assertOk();

    $firstCompletion = $user->fresh()->onboarding_completed_at;
    expect($firstCompletion)->not()->toBeNull();

    Carbon::setTestNow('2026-03-10 12:00:00');

    $this->actingAs($user)
        ->postJson(route('onboarding.complete'))
        ->assertOk();

    $secondCompletion = $user->fresh()->onboarding_completed_at;
    expect($secondCompletion?->toDateTimeString())->toBe($firstCompletion?->toDateTimeString());

    Carbon::setTestNow();
});

test('resetting onboarding shows modal again on next page load', function () {
    $user = User::factory()->create([
        'onboarding_completed_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('onboarding.reset'))
        ->assertRedirect();

    $this->actingAs($user)
        ->get(route('task.index'))
        ->assertOk()
        ->assertSee('data-onboarding-modal', false);
});
