<?php

namespace App\Actions\Packages;

use App\Enums\PackageStatus;
use App\Models\Package;
use Carbon\CarbonInterface;

class CalculatePackageStorageAmountAction
{
    private const string StorageTimezone = 'America/La_Paz';

    /**
     * @return array{baseAmount: string, daysStored: int, extraWeeks: int, surchargeAmount: string, totalAmount: string}
     */
    public function execute(Package $package, ?CarbonInterface $asOf = null): array
    {
        $baseCents = $this->toCents($package->storage_price);
        $incrementCents = $this->toCents($package->weekly_storage_increment);
        $daysStored = $this->daysStored($package, $asOf);
        $extraWeeks = $package->status === PackageStatus::Cancelled
            ? 0
            : intdiv(max(0, $daysStored - 1), 7);
        $surchargeCents = $extraWeeks * $incrementCents;
        $calculatedTotalCents = $baseCents + $surchargeCents;
        $totalCents = $package->status === PackageStatus::Delivered && $package->final_storage_amount !== null
            ? $this->toCents($package->final_storage_amount)
            : $calculatedTotalCents;

        return [
            'baseAmount' => $this->fromCents($baseCents),
            'daysStored' => $daysStored,
            'extraWeeks' => $extraWeeks,
            'surchargeAmount' => $this->fromCents(max(0, $totalCents - $baseCents)),
            'totalAmount' => $this->fromCents($totalCents),
        ];
    }

    private function daysStored(Package $package, ?CarbonInterface $asOf): int
    {
        if ($package->received_at === null) {
            return 0;
        }

        $receivedDate = $package->received_at->copy()->timezone(self::StorageTimezone)->startOfDay();
        $endingAt = match ($package->status) {
            PackageStatus::Delivered => $package->delivered_at ?? $asOf ?? now(self::StorageTimezone),
            PackageStatus::Cancelled => $package->cancelled_at ?? $package->received_at,
            default => $asOf ?? now(self::StorageTimezone),
        };
        $endingDate = $endingAt->copy()->timezone(self::StorageTimezone)->startOfDay();

        if ($endingDate->lessThan($receivedDate)) {
            return 1;
        }

        return (int) $receivedDate->diffInDays($endingDate) + 1;
    }

    private function toCents(string|int|float|null $amount): int
    {
        return (int) round((float) ($amount ?? 0) * 100);
    }

    private function fromCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
