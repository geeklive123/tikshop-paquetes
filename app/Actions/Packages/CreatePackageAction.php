<?php

namespace App\Actions\Packages;

use App\Enums\PackageEventType;
use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\PackageEvent;
use App\Models\User;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Facades\DB;

class CreatePackageAction
{
    private const int MaximumDailySequence = 9999;

    /**
     * @param  array{sender_name: string, sender_phone: string, recipient_name: string, recipient_phone: string, description?: string|null, notes?: string|null}  $data
     */
    public function execute(User $user, array $data): Package
    {
        return DB::transaction(function () use ($user, $data): Package {
            $company = $user->company()->firstOrFail();
            $branch = $company->branches()
                ->where('name', (string) config('tikshop.main_branch.name'))
                ->where('active', true)
                ->firstOrFail();
            $receivedAt = now();

            $package = Package::query()->create([
                ...$data,
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'tracking_code' => $this->nextTrackingCode($receivedAt),
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
