<?php

namespace App\Actions\Packages;

use App\Enums\PackageEventType;
use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\PackageEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelPackageAction
{
    public function execute(User $user, Package $package, string $reason): Package
    {
        return DB::transaction(function () use ($user, $package, $reason): Package {
            $lockedPackage = Package::query()
                ->whereKey($package->getKey())
                ->where('company_id', $user->company_id)
                ->lockForUpdate()
                ->first();

            if ($lockedPackage === null) {
                throw (new ModelNotFoundException)->setModel(Package::class);
            }

            if ($lockedPackage->status === PackageStatus::Delivered) {
                throw ValidationException::withMessages([
                    'package' => 'Un paquete entregado no puede anularse.',
                ]);
            }

            if ($lockedPackage->status === PackageStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'package' => 'Este paquete ya fue anulado.',
                ]);
            }

            $cancelledAt = now();
            $revokedTokenCount = $lockedPackage->pickupTokens()
                ->whereNull('used_at')
                ->whereNull('revoked_at')
                ->update(['revoked_at' => $cancelledAt]);

            $lockedPackage->update([
                'status' => PackageStatus::Cancelled,
                'cancelled_at' => $cancelledAt,
                'cancelled_by' => $user->id,
                'cancellation_reason' => $reason,
            ]);

            PackageEvent::query()->create([
                'package_id' => $lockedPackage->id,
                'user_id' => $user->id,
                'event' => PackageEventType::PackageCancelled,
                'metadata' => [
                    'reason' => $reason,
                    'revoked_pickup_tokens' => $revokedTokenCount,
                ],
            ]);

            return $lockedPackage->fresh();
        }, 5);
    }
}
