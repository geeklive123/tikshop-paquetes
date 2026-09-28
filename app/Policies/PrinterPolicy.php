<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Printer;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PrinterPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasPrinterAccess($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Printer $printer): Response
    {
        if (! $this->hasPrinterAccess($user)) {
            return Response::deny();
        }

        return $user->company_id === $printer->company_id
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
    public function update(User $user, Printer $printer): Response
    {
        $viewResponse = $this->view($user, $printer);

        if (! $viewResponse->allowed()) {
            return $viewResponse;
        }

        return $this->canManage($user) ? Response::allow() : Response::deny();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Printer $printer): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Printer $printer): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Printer $printer): bool
    {
        return false;
    }

    private function hasPrinterAccess(User $user): bool
    {
        return $user->company_id !== null
            && in_array($user->role, UserRole::cases(), true);
    }

    private function canManage(User $user): bool
    {
        return $user->company_id !== null
            && in_array($user->role, [UserRole::Owner, UserRole::Admin], true);
    }
}
