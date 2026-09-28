<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\PrintJob;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PrintJobPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasAccess($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PrintJob $printJob): Response
    {
        if (! $this->hasAccess($user)) {
            return Response::deny();
        }

        if ($user->company_id !== $printJob->company_id) {
            return Response::denyAsNotFound();
        }

        return $this->canManage($user) || $printJob->requested_by === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->hasAccess($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PrintJob $printJob): Response
    {
        if ($user->company_id !== $printJob->company_id) {
            return Response::denyAsNotFound();
        }

        return $this->canManage($user) ? Response::allow() : Response::deny();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PrintJob $printJob): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PrintJob $printJob): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PrintJob $printJob): bool
    {
        return false;
    }

    private function hasAccess(User $user): bool
    {
        return $user->company_id !== null && in_array($user->role, UserRole::cases(), true);
    }

    private function canManage(User $user): bool
    {
        return in_array($user->role, [UserRole::Owner, UserRole::Admin], true);
    }
}
