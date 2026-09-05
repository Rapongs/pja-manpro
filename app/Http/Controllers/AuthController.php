<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email atau password tidak sesuai.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->forget('guest_mode');

        return to_route('projects.index');
    }

    public function guest(Request $request): RedirectResponse
    {
        $guest = User::firstOrCreate(
            ['email' => 'guest@ruangproyek.test'],
            ['name' => 'Guest Demo', 'password' => 'guest-demo-password']
        );

        Auth::login($guest);
        $request->session()->regenerate();
        $request->session()->put('guest_mode', true);

        return to_route('projects.index');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login');
    }
}
