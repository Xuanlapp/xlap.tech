<?php

namespace App\Livewire\Pages\Admin;

use App\Services\UserAccessService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Session;
use Livewire\Component;

class ListUser extends Component
{
    #[Session(key: 'admin.users.name')]
    public string $name = '';

    #[Session(key: 'admin.users.email')]
    public string $email = '';

    public string $password = '';

    #[Session(key: 'admin.users.is-admin')]
    public bool $is_admin = false;

    /** @var array<int, int|string> */
    #[Session(key: 'admin.users.selected-products')]
    public array $selectedProducts = [];

    public function createUser(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(12)->mixedCase()->numbers()],
            'is_admin' => ['boolean'],
            'selectedProducts' => ['array'],
            'selectedProducts.*' => ['integer', 'exists:products,id'],
        ]);

        app(UserAccessService::class)->createUser($validated);

        $this->reset(['name', 'email', 'password', 'is_admin', 'selectedProducts']);
        $this->dispatch('user-created');
    }

    public function toggleProduct(int $userId, int $productId): void
    {
        app(UserAccessService::class)->toggleProduct($userId, $productId);
    }

    public function render(): View
    {
        $service = app(UserAccessService::class);

        return view('livewire.pages.admin.list-user', [
            'products' => $service->activeProducts(),
            'users' => $service->users(),
        ]);
    }
}
