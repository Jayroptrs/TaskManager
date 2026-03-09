<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionsController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $attributes = [
            'email' => trim((string) $request->input('email')),
            'password' => (string) $request->input('password'),
        ];

        if (!Auth::attempt($attributes)) {
            return back()
                ->withErrors(['password' => __('auth.invalid_credentials')])
                ->withInput($request->only('email'));
        }

        $request->session()->regenerate();

        return redirect()->intended('/')->with('success', __('messages.login_success'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
