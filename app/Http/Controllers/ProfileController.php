<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use App\Models\User;
use App\Notifications\EmailChanged;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = User::query()->findOrFail((int) Auth::id());

        return view('profile.edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request)
    {

        $user = User::query()->findOrFail((int) Auth::id());

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['nullable', 'string'],
            'password' => ['nullable', Password::defaults(), 'confirmed'],
            'avatar' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($request->filled('password')) {
            $request->validate([
                'current_password' => ['required', 'current_password'],
            ]);
        }

        $originalEmail = $user->email;

        $payload = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $payload['password'] = $request->password;
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $payload['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($payload);

        if($originalEmail !== $request->email) {
            Notification::route('mail', $originalEmail)
                ->notify(new EmailChanged($user, $originalEmail));
        }

        return redirect()->route('profile.edit')->with('success', 'Profiel gewijzigd!');
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
        ]);

        $user = User::query()->findOrFail((int) Auth::id());

        if (! Hash::check((string) $request->input('current_password'), $user->password)) {
            return back()->withErrors([
                'delete_account' => __('profile.delete_password_invalid'),
            ]);
        }

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $user->delete();

        return redirect('/')->with('success', __('messages.account_deleted'));
    }

    public function destroyAvatar(Request $request)
    {
        $user = User::query()->findOrFail((int) Auth::id());

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->update(['avatar_path' => null]);
        }

        return back()->with('success', __('messages.avatar_removed'));
    }
}
