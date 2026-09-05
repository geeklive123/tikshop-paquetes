<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Package;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PackagePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasPackageAccess($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Package $package): Response
    {
        if (! $this->hasPackageAccess($user)) {
            return Response::deny();
        }

        return $user->company_id === $package->company_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->hasPackageAccess($user);
    }

    public function generatePickupToken(User $user, Package $package): Response
    {
        $viewResponse = $this->view($user, $package);

        if (! $viewResponse->allowed()) {
            return $viewResponse;
        }

        return in_array($user->role, [UserRole::Owner, UserRole::Admin], true)
            ? Response::allow()
            : Response::deny();
    }

    public function deliver(User $user, Package $package): Response
    {
        return $this->view($user, $package);
    }

    private function hasPackageAccess(User $user): bool
    {
        return $user->company_id !== null
            && in_array($user->role, UserRole::cases(), true);
    }
}
