<?php

namespace App\Http\Controllers;

use App\Actions\Users\CreateUserAction;
use App\Actions\Users\UpdateUserAction;
use App\Enums\UserRole;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        /** @var User $actor */
        $actor = $request->user();
        $users = User::query()
            ->whereBelongsTo($actor->company)
            ->when(
                ! $actor->role->canManage(UserRole::Owner),
                fn ($query) => $query->where('role', '!=', UserRole::Owner),
            )
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15);

        return view('users.index', ['users' => $users]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', User::class);

        /** @var User $actor */
        $actor = $request->user();

        return view('users.create', [
            'roles' => $actor->role->manageableRoles(),
        ]);
    }

    public function store(StoreUserRequest $request, CreateUserAction $createUser): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $user = $createUser->execute($actor, $request->validated());

        return redirect()
            ->route('users.edit', $user)
            ->with('status', 'Usuario creado correctamente.');
    }

    public function edit(Request $request, User $user): View
    {
        Gate::authorize('update', $user);

        /** @var User $actor */
        $actor = $request->user();

        return view('users.edit', [
            'managedUser' => $user,
            'roles' => $actor->role->manageableRoles(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $updateUser): RedirectResponse
    {
        $updateUser->execute($user, $request->validated());

        return redirect()
            ->route('users.edit', $user)
            ->with('status', 'Usuario actualizado correctamente.');
    }
}
