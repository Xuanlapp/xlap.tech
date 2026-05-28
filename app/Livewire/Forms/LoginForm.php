<?php

namespace App\Livewire\Forms;

use App\Models\User;
use App\Support\LoginSecurity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate('required|string|max:255')]
    public string $login = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $remember = false;

    public string $website = '';

    public int $startedAt = 0;

    public string $turnstileToken = '';

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        app(LoginSecurity::class)->assertCanAttempt(
            $this->login,
            $this->website,
            $this->startedAt,
            $this->turnstileToken,
            'form.login'
        );

        $loginField = filter_var($this->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';

        $security = app(LoginSecurity::class);
        $user = User::where($loginField, $this->login)->first();
        $passwordHash = $user?->password ?? $security->dummyPasswordHash();

        if (! $user || ! Hash::check($this->password, $passwordHash)) {
            app(LoginSecurity::class)->recordFailedAttempt($this->login);

            throw ValidationException::withMessages([
                'form.login' => trans('auth.failed'),
            ]);
        }

        Auth::login($user, $this->remember);

        app(LoginSecurity::class)->clearSuccessfulAttempt($this->login);
    }
}
