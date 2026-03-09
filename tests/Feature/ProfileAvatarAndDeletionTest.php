<?php

use App\Models\Task;
use App\Models\User;
use App\Notifications\EmailChanged;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

test('user can upload profile avatar', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('success');

    $fresh = $user->fresh();
    expect($fresh->avatar_path)->not->toBeNull();
    Storage::disk('public')->assertExists($fresh->avatar_path);
});

test('uploading a new avatar removes previous avatar file', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $oldPath = 'avatars/old-avatar.jpg';
    Storage::disk('public')->put($oldPath, 'old');
    $user->update(['avatar_path' => $oldPath]);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('new-avatar.jpg'),
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('success');

    $fresh = $user->fresh();
    expect($fresh->avatar_path)->not->toBeNull();
    expect($fresh->avatar_path)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($fresh->avatar_path);
});

test('user can remove existing avatar', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $path = 'avatars/existing-avatar.jpg';
    Storage::disk('public')->put($path, 'content');
    $user->update(['avatar_path' => $path]);

    $this->actingAs($user)
        ->delete(route('profile.avatar.destroy'))
        ->assertStatus(302);

    expect($user->fresh()->avatar_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('removing avatar without existing avatar is a no-op and still redirects back', function () {
    Storage::fake('public');
    $user = User::factory()->create(['avatar_path' => null]);

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.avatar.destroy'))
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('success');

    expect($user->fresh()->avatar_path)->toBeNull();
});

test('guest cannot remove avatar', function () {
    $this->delete(route('profile.avatar.destroy'))
        ->assertRedirect(route('login'));
});

test('profile page shows remove avatar button only when avatar exists', function () {
    $withAvatar = User::factory()->create(['avatar_path' => 'avatars/has-avatar.jpg']);
    $withoutAvatar = User::factory()->create(['avatar_path' => null]);

    $this->actingAs($withAvatar)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee(__('profile.remove_avatar'));

    $this->actingAs($withoutAvatar)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertDontSee(__('profile.remove_avatar'));
});

test('deleting account requires correct current password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('correct-password'),
    ]);

    $this->actingAs($user)
        ->delete(route('profile.destroy'), [
            'current_password' => 'wrong-password',
        ])
        ->assertSessionHasErrors('delete_account');

    $this->assertDatabaseHas('users', ['id' => $user->id]);
});

test('user can delete own account and is logged out', function () {
    Storage::fake('public');
    $user = User::factory()->create([
        'password' => Hash::make('correct-password'),
    ]);
    $avatarPath = 'avatars/to-delete.jpg';
    Storage::disk('public')->put($avatarPath, 'avatar');
    $user->update(['avatar_path' => $avatarPath]);

    $taskImagePath = 'ideas/user-task-to-delete.jpg';
    Storage::disk('public')->put($taskImagePath, 'task-image');
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'image_path' => $taskImagePath,
    ]);
    $task->comments()->create([
        'user_id' => $user->id,
        'body' => 'Mijn comment',
    ]);

    $this->actingAs($user)
        ->delete(route('profile.destroy'), [
            'current_password' => 'correct-password',
        ])
        ->assertRedirect('/')
        ->assertSessionHas('success');

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['id' => $user->id]);
    $this->assertDatabaseMissing('ideas', ['id' => $task->id]);
    $this->assertDatabaseMissing('task_comments', [
        'user_id' => $user->id,
        'body' => 'Mijn comment',
    ]);
    Storage::disk('public')->assertMissing($avatarPath);
    Storage::disk('public')->assertMissing($taskImagePath);
});

test('guest cannot delete account endpoint', function () {
    $this->delete(route('profile.destroy'), [
        'current_password' => 'irrelevant',
    ])->assertRedirect(route('login'));
});

test('changing email sends email changed notification to old email address', function () {
    Notification::fake();
    $user = User::factory()->create([
        'email' => 'old@example.com',
    ]);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => 'new@example.com',
        ])
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh()->email)->toBe('new@example.com');

    Notification::assertSentOnDemand(EmailChanged::class, function (EmailChanged $notification, array $channels, object $notifiable) {
        return in_array('mail', $channels, true)
            && ($notifiable->routes['mail'] ?? null) === 'old@example.com';
    });
});

test('keeping same email does not send email changed notification', function () {
    Notification::fake();
    $user = User::factory()->create([
        'email' => 'same@example.com',
    ]);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Nieuwe Naam',
            'email' => 'same@example.com',
        ])
        ->assertRedirect(route('profile.edit'));

    Notification::assertNothingSent();
});
