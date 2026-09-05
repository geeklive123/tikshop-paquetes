<?php

namespace App\Http\Controllers;

use App\Actions\Users\UpdateUserAction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class UserStatusController extends Controller
{
    public function __invoke(User $user, UpdateUserAction $updateUser): RedirectResponse
    {
        Gate::authorize('update', $user);

        $updatedUser = $updateUser->execute($user, ['active' => ! $user->active]);

        return redirect()
            ->route('users.index')
            ->with('status', $updatedUser->active ? 'Usuario activado correctamente.' : 'Usuario desactivado correctamente.');
    }
}
