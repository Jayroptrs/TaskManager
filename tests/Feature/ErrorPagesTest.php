<?php

use Illuminate\Support\Facades\Route;

test('custom 404 page is rendered', function () {
    config(['app.debug' => false]);

    $this->get('/__missing-custom-error-page')
        ->assertStatus(404)
        ->assertSee(__('ui.error_404_title'))
        ->assertSee(__('ui.error_404_description'))
        ->assertSee(__('ui.error_404_hint'))
        ->assertSee(__('ui.error_home'))
        ->assertSee(__('ui.error_back'));
});

test('custom error page is rendered for common status codes', function (
    int $status,
    string $titleKey,
    string $descriptionKey,
    string $hintKey,
    string $actionKey
) {
    config(['app.debug' => false]);

    $path = '/__test/error-'.$status.'-'.bin2hex(random_bytes(4));
    Route::middleware('web')->get($path, fn () => abort($status));

    $this->get($path)
        ->assertStatus($status)
        ->assertSee(__($titleKey))
        ->assertSee(__($descriptionKey))
        ->assertSee(__($hintKey))
        ->assertSee(__('ui.error_home'))
        ->assertSee(__($actionKey));
})->with([
    [401, 'ui.error_401_title', 'ui.error_401_description', 'ui.error_401_hint', 'ui.error_back'],
    [403, 'ui.error_403_title', 'ui.error_403_description', 'ui.error_403_hint', 'ui.error_back'],
    [419, 'ui.error_419_title', 'ui.error_419_description', 'ui.error_419_hint', 'ui.error_reload'],
    [429, 'ui.error_429_title', 'ui.error_429_description', 'ui.error_429_hint', 'ui.error_reload'],
    [500, 'ui.error_500_title', 'ui.error_500_description', 'ui.error_500_hint', 'ui.error_reload'],
    [502, 'ui.error_502_title', 'ui.error_502_description', 'ui.error_502_hint', 'ui.error_reload'],
    [503, 'ui.error_503_title', 'ui.error_503_description', 'ui.error_503_hint', 'ui.error_reload'],
]);
