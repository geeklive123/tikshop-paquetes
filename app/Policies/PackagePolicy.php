<?php

namespace App\Policies;

use App\Enums\PackageStatus;
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

    public function sharePickupQr(User $user, Package $package): Response
    {
        return $this->view($user, $package);
    }

    public function deliver(User $user, Package $package): Response
    {
        return $this->view($user, $package);
    }

    public function update(User $user, Package $package): Response
    {
        return $this->manage($user, $package);
    }

    public function cancel(User $user, Package $package): Response
    {
        return $this->manage($user, $package);
    }

    private function manage(User $user, Package $package): Response
    {
        $viewResponse = $this->view($user, $package);

        if (! $viewResponse->allowed()) {
            return $viewResponse;
        }

        if (in_array($package->status, [PackageStatus::Delivered, PackageStatus::Cancelled], true)) {
            return Response::deny();
        }

        return in_array($user->role, [UserRole::Owner, UserRole::Admin], true)
            ? Response::allow()
            : Response::deny();
    }

    private function hasPackageAccess(User $user): bool
    {
        return $user->company_id !== null
            && in_array($user->role, UserRole::cases(), true);
    }
}
