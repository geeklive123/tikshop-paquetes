<?php

namespace App\Actions\Packages;

use App\Enums\PackageEventType;
use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\PackageEvent;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdatePackageAction
{
    /** @param array{seller_id: int|string, package_category_id: int|string, storage_code: string, recipient_name: string, recipient_phone: string, description?: string|null, notes?: string|null} $data */
    public function execute(User $user, Package $package, array $data): Package
    {
        return DB::transaction(function () use ($user, $package, $data): Package {
            $lockedPackage = Package::query()
                ->whereKey($package->getKey())
                ->where('company_id', $user->company_id)
                ->lockForUpdate()
                ->first();

            if ($lockedPackage === null) {
                throw (new ModelNotFoundException)->setModel(Package::class);
            }

            if (in_array($lockedPackage->status, [PackageStatus::Delivered, PackageStatus::Cancelled], true)) {
                throw ValidationException::withMessages([
                    'package' => 'Un paquete entregado o anulado no puede editarse.',
                ]);
            }

            $seller = Seller::query()
                ->whereKey($data['seller_id'])
                ->where('company_id', $lockedPackage->company_id)
                ->when(
                    (int) $data['seller_id'] !== $lockedPackage->seller_id,
                    fn (Builder $query): Builder => $query->where('active', true),
                )
                ->firstOrFail();
            $category = PackageCategory::query()
                ->whereKey($data['package_category_id'])
                ->where('company_id', $lockedPackage->company_id)
                ->when(
                    (int) $data['package_category_id'] !== $lockedPackage->package_category_id,
                    fn (Builder $query): Builder => $query->where('active', true),
                )
                ->firstOrFail();

            $attributes = [
                'seller_id' => $seller->id,
                'package_category_id' => $category->id,
                'storage_code' => $data['storage_code'],
                'recipient_name' => $data['recipient_name'],
                'recipient_phone' => $data['recipient_phone'],
                'description' => $data['description'] ?? null,
                'notes' => $data['notes'] ?? null,
            ];

            if ($seller->id !== $lockedPackage->seller_id) {
                $attributes['sender_name'] = $seller->snapshotName();
                $attributes['sender_phone'] = $seller->phone;
            }

            if ($category->id !== $lockedPackage->package_category_id) {
                $attributes['storage_price'] = $category->price;
            }

            $lockedPackage->fill($attributes);
            $changes = $this->changedAttributes($lockedPackage);

            if ($changes !== []) {
                $lockedPackage->save();

                PackageEvent::query()->create([
                    'package_id' => $lockedPackage->id,
                    'user_id' => $user->id,
                    'event' => PackageEventType::PackageUpdated,
                    'metadata' => ['changes' => $changes],
                ]);
            }

            return $lockedPackage->fresh();
        }, 5);
    }

    /** @return array<string, array{from: mixed, to: mixed}> */
    private function changedAttributes(Package $package): array
    {
        $changes = [];

        foreach ($package->getDirty() as $attribute => $newValue) {
            $changes[$attribute] = [
                'from' => $package->getRawOriginal($attribute),
                'to' => $newValue,
            ];
        }

        return $changes;
    }
}
