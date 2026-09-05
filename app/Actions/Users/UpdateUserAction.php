<?php

namespace App\Actions\Users;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateUserAction
{
    /** @param array{name?: string, email?: string, password?: string|null, role?: string, active?: bool} $data */
    public function execute(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $user = User::query()->lockForUpdate()->findOrFail($user->getKey());

            $this->ensureAnActiveOwnerRemains($user, $data);

            if (($data['password'] ?? null) === null) {
                unset($data['password']);
            }

            $user->update($data);

            return $user;
        }, 5);
    }

    /** @param array{role?: string, active?: bool} $data */
    private function ensureAnActiveOwnerRemains(User $user, array $data): void
    {
        if ($user->role !== UserRole::Owner || ! $user->active) {
            return;
        }

        $newRole = isset($data['role']) ? UserRole::from($data['role']) : $user->role;
        $willRemainActive = $data['active'] ?? $user->active;

        if ($newRole === UserRole::Owner && $willRemainActive) {
            return;
        }

        $activeOwnerIds = User::query()
            ->where('company_id', $user->company_id)
            ->where('role', UserRole::Owner)
            ->where('active', true)
            ->lockForUpdate()
            ->pluck('id');

        if ($activeOwnerIds->count() <= 1) {
            throw ValidationException::withMessages([
                'active' => 'La empresa debe conservar al menos un owner activo.',
            ]);
        }
    }
}
