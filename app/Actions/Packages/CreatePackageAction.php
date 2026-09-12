<?php

namespace App\Actions\Packages;

use App\Enums\PackageEventType;
use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\PackageEvent;
use App\Models\Seller;
use App\Models\User;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Facades\DB;

class CreatePackageAction
{
    private const int MaximumDailySequence = 9999;

    /**
     * @param  array{seller_id: int|string, package_category_id: int|string, storage_code: string, recipient_name: string, recipient_phone: string, description?: string|null, notes?: string|null}  $data
     */
    public function execute(User $user, array $data): Package
    {
        return DB::transaction(function () use ($user, $data): Package {
            $company = $user->company()->firstOrFail();
            $branch = $company->branches()
                ->where('name', (string) config('tikshop.main_branch.name'))
                ->where('active', true)
                ->firstOrFail();
            $category = PackageCategory::query()
                ->whereKey($data['package_category_id'])
                ->whereBelongsTo($company)
                ->where('active', true)
                ->lockForUpdate()
                ->firstOrFail();
            $seller = Seller::query()
                ->whereKey($data['seller_id'])
                ->whereBelongsTo($company)
                ->where('active', true)
                ->firstOrFail();
            $receivedAt = now();

            $package = Package::query()->create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'seller_id' => $seller->id,
                'package_category_id' => $category->id,
                'storage_price' => $category->price,
                'storage_code' => $data['storage_code'],
                'tracking_code' => $this->nextTrackingCode($receivedAt),
                'sender_name' => $seller->snapshotName(),
                'sender_phone' => $seller->phone,
                'recipient_name' => $data['recipient_name'],
                'recipient_phone' => $data['recipient_phone'],
                'description' => $data['description'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => PackageStatus::Received,
                'received_at' => $receivedAt,
                'received_by' => $user->id,
            ]);

            PackageEvent::query()->create([
                'package_id' => $package->id,
                'user_id' => $user->id,
                'event' => PackageEventType::PackageCreated,
            ]);

            return $package;
        }, 5);
    }

    private function nextTrackingCode(CarbonInterface $receivedAt): string
    {
        $sequenceDate = $receivedAt->toDateString();

        DB::table('package_tracking_sequences')->insertOrIgnore([
            'sequence_date' => $sequenceDate,
            'last_number' => 0,
        ]);

        $lastNumber = (int) DB::table('package_tracking_sequences')
            ->where('sequence_date', $sequenceDate)
            ->lockForUpdate()
            ->value('last_number');
        $nextNumber = $lastNumber + 1;

        if ($nextNumber > self::MaximumDailySequence) {
            throw new DomainException('The daily package tracking code limit has been reached.');
        }

        DB::table('package_tracking_sequences')
            ->where('sequence_date', $sequenceDate)
            ->update(['last_number' => $nextNumber]);

        return sprintf('TIK-%s-%04d', $receivedAt->format('ymd'), $nextNumber);
    }
}
