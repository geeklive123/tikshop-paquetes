<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\SellerCommissionSettlement;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SellerCommissionSettlementPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null && in_array($user->role, UserRole::cases(), true);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SellerCommissionSettlement $sellerCommissionSettlement): Response
    {
        if (! $this->viewAny($user)) {
            return Response::deny();
        }

        return $user->company_id === $sellerCommissionSettlement->company_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->company_id !== null
            && in_array($user->role, [UserRole::Owner, UserRole::Admin], true);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SellerCommissionSettlement $sellerCommissionSettlement): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SellerCommissionSettlement $sellerCommissionSettlement): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SellerCommissionSettlement $sellerCommissionSettlement): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SellerCommissionSettlement $sellerCommissionSettlement): bool
    {
        return false;
    }
}
