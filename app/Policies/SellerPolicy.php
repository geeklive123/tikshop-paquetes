<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SellerPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasSellerAccess($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Seller $seller): Response
    {
        if (! $this->hasSellerAccess($user)) {
            return Response::deny();
        }

        return $user->company_id === $seller->company_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->hasSellerAccess($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Seller $seller): Response
    {
        $viewResponse = $this->view($user, $seller);

        if (! $viewResponse->allowed()) {
            return $viewResponse;
        }

        return in_array($user->role, [UserRole::Owner, UserRole::Admin], true)
            ? Response::allow()
            : Response::deny();
    }

    public function toggleStatus(User $user, Seller $seller): Response
    {
        return $this->update($user, $seller);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Seller $seller): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Seller $seller): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Seller $seller): bool
    {
        return false;
    }

    private function hasSellerAccess(User $user): bool
    {
        return $user->company_id !== null
            && in_array($user->role, UserRole::cases(), true);
    }
}
