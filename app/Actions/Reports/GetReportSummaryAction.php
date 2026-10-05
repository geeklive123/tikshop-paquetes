<?php

namespace App\Actions\Reports;

use App\Actions\Packages\CalculatePackageStorageAmountAction;
use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\User;
use Carbon\CarbonInterface;

class GetReportSummaryAction
{
    public function __construct(private CalculatePackageStorageAmountAction $calculateStorageAmount) {}

    /** @return array{received: int, pending: int, delivered: int, cancelled: int, baseStorageAmount: float, storageSurchargeAmount: float, storageAmount: float, activeSellers: int} */
    public function execute(User $user, CarbonInterface $start, CarbonInterface $end): array
    {
        $packages = Package::query()
            ->where('company_id', $user->company_id)
            ->whereBetween('received_at', [$start, $end])
            ->get();
        $amounts = $packages
            ->reject(fn (Package $package): bool => $package->status === PackageStatus::Cancelled)
            ->map(fn (Package $package): array => $this->calculateStorageAmount->execute($package));

        return [
            'received' => $packages->count(),
            'pending' => $packages->whereIn('status', [PackageStatus::Received, PackageStatus::ReadyForPickup])->count(),
            'delivered' => $packages->where('status', PackageStatus::Delivered)->count(),
            'cancelled' => $packages->where('status', PackageStatus::Cancelled)->count(),
            'baseStorageAmount' => $amounts->sum(fn (array $amount): float => (float) $amount['baseAmount']),
            'storageSurchargeAmount' => $amounts->sum(fn (array $amount): float => (float) $amount['surchargeAmount']),
            'storageAmount' => $amounts->sum(fn (array $amount): float => (float) $amount['totalAmount']),
            'activeSellers' => $packages->pluck('seller_id')->filter()->unique()->count(),
        ];
    }
}
