<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\LoginSecurity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function login(Request $request, LoginSecurity $security)
    {
        $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'website' => ['nullable', 'string'],
            'started_at' => ['nullable', 'integer'],
            'cf-turnstile-response' => ['nullable', 'string'],
        ]);

        $login = $request->input('login');
        $loginField = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';

        $security->assertCanAttempt(
            $login,
            $request->input('website'),
            $request->integer('started_at'),
            $request->input('cf-turnstile-response'),
            'login'
        );

        $user = User::where($loginField, $login)->first();
        $passwordHash = $user?->password ?? $security->dummyPasswordHash();

        if (! $user || ! Hash::check($request->input('password'), $passwordHash)) {
            $security->recordFailedAttempt($login);

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        Auth::login($user, $request->boolean('remember'));

        $security->clearSuccessfulAttempt($login);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', [], false));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
