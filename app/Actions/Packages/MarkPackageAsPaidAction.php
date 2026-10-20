<?php

namespace App\Actions\Packages;

use App\Enums\PackageEventType;
use App\Enums\PackagePaymentStatus;
use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\PackageEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class MarkPackageAsPaidAction
{
    public function __construct(private CalculatePackageStorageAmountAction $calculateStorageAmount) {}

    public function execute(User $user, Package $package): Package
    {
        return DB::transaction(function () use ($user, $package): Package {
            $lockedPackage = Package::query()
                ->whereKey($package->id)
                ->where('company_id', $user->company_id)
                ->lockForUpdate()
                ->first();

            if ($lockedPackage === null) {
                throw (new ModelNotFoundException)->setModel(Package::class);
            }

            Gate::forUser($user)->authorize('markPaid', $lockedPackage);

            if ($lockedPackage->status === PackageStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'payment' => 'Un paquete cancelado no puede marcarse como pagado.',
                ]);
            }

            if ($lockedPackage->payment_status === PackagePaymentStatus::Paid) {
                return $lockedPackage;
            }

            if ($lockedPackage->payment_status !== PackagePaymentStatus::Pending) {
                throw ValidationException::withMessages([
                    'payment' => 'El paquete histórico no admite cambios automáticos de pago.',
                ]);
            }

            $paidAt = now();
            $storageAmount = $this->calculateStorageAmount->execute($lockedPackage, $paidAt);

            $lockedPackage->update([
                'payment_status' => PackagePaymentStatus::Paid,
                'paid_amount' => $storageAmount['totalAmount'],
                'paid_at' => $paidAt,
                'paid_by' => $user->id,
            ]);

            PackageEvent::query()->create([
                'package_id' => $lockedPackage->id,
                'user_id' => $user->id,
                'event' => PackageEventType::PackagePaymentMarkedPaid,
                'metadata' => ['paid_amount' => $storageAmount['totalAmount']],
            ]);

            return $lockedPackage;
        }, 5);
    }
}
