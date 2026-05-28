<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateUserWithProductAccess
{
    /**
     * Create a user and grant selected product pages.
     *
     * @param  array{name: string, email: string, password: string, is_admin?: bool, selectedProducts?: array<int, int|string>}  $data
     */
    public function __invoke(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'status' => 'active',
            'is_admin' => (bool) ($data['is_admin'] ?? false),
        ]);

        $productIds = collect($data['selectedProducts'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();

        $user->products()->sync($productIds);

        return $user;
    }
}
