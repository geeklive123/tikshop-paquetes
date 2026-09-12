<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\PackageCategory;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PackageCategoryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->canManage($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PackageCategory $packageCategory): Response
    {
        if (! $this->canManage($user)) {
            return Response::deny();
        }

        return $user->company_id === $packageCategory->company_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PackageCategory $packageCategory): Response
    {
        return $this->view($user, $packageCategory);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PackageCategory $packageCategory): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PackageCategory $packageCategory): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PackageCategory $packageCategory): bool
    {
        return false;
    }

    private function canManage(User $user): bool
    {
        return $user->company_id !== null
            && in_array($user->role, [UserRole::Owner, UserRole::Admin], true);
    }
}
