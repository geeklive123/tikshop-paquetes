<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\PrinterAgent;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PrinterAgentPolicy
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
    public function view(User $user, PrinterAgent $printerAgent): Response
    {
        if (! $this->canManage($user)) {
            return Response::deny();
        }

        return $user->company_id === $printerAgent->company_id
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
    public function update(User $user, PrinterAgent $printerAgent): Response
    {
        return $this->view($user, $printerAgent);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PrinterAgent $printerAgent): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PrinterAgent $printerAgent): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PrinterAgent $printerAgent): bool
    {
        return false;
    }

    private function canManage(User $user): bool
    {
        return $user->company_id !== null
            && in_array($user->role, [UserRole::Owner, UserRole::Admin], true);
    }
}
