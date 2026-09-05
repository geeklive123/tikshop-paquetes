<?php

namespace App\Actions\Packages;

use App\Enums\PackageEventType;
use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\PackageEvent;
use App\Models\PackagePickupToken;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DeliverPackageAction
{
    public function execute(User $user, string $input): Package
    {
        return DB::transaction(function () use ($user, $input): Package {
            $tokenReference = PackagePickupToken::query()
                ->where('token_hash', ResolvePickupTokenAction::hashFromInput($input))
                ->first();

            if ($tokenReference === null) {
                throw ValidationException::withMessages(['token' => 'El código QR no es válido.']);
            }

            $package = Package::query()
                ->whereKey($tokenReference->package_id)
                ->where('company_id', $user->company_id)
                ->lockForUpdate()
                ->first();

            if ($package === null) {
                throw (new ModelNotFoundException)->setModel(Package::class);
            }

            Gate::forUser($user)->authorize('deliver', $package);

            $pickupToken = PackagePickupToken::query()
                ->whereKey($tokenReference->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureDeliverable($package, $pickupToken);

            $deliveredAt = now();
            $package->update([
                'status' => PackageStatus::Delivered,
                'delivered_at' => $deliveredAt,
                'delivered_by' => $user->id,
            ]);
            $pickupToken->update(['used_at' => $deliveredAt]);

            PackageEvent::query()->create([
                'package_id' => $package->id,
                'user_id' => $user->id,
                'event' => PackageEventType::PackageDelivered,
                'metadata' => ['pickup_token_id' => $pickupToken->id],
            ]);

            return $package;
        }, 5);
    }

    private function ensureDeliverable(Package $package, PackagePickupToken $pickupToken): void
    {
        if ($pickupToken->used_at !== null) {
            throw ValidationException::withMessages(['token' => 'Este código ya fue utilizado.']);
        }

        if ($pickupToken->revoked_at !== null) {
            throw ValidationException::withMessages(['token' => 'Este código fue reemplazado y ya no es válido.']);
        }

        if ($pickupToken->expires_at?->isPast()) {
            throw ValidationException::withMessages(['token' => 'Este código QR ha expirado.']);
        }

        if ($package->status === PackageStatus::Cancelled) {
            throw ValidationException::withMessages(['token' => 'Este paquete fue cancelado y no puede entregarse.']);
        }

        if ($package->status === PackageStatus::Delivered) {
            throw ValidationException::withMessages(['token' => 'Este paquete ya fue entregado.']);
        }

        if ($package->status !== PackageStatus::ReadyForPickup) {
            throw ValidationException::withMessages(['token' => 'Este paquete todavía no está listo para entregarse.']);
        }
    }
}
