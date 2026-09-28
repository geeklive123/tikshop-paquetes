<?php

namespace App\Actions\Reports;

use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\User;
use Carbon\CarbonInterface;

class GetReportSummaryAction
{
    /** @return array{received: int, pending: int, delivered: int, cancelled: int, storageAmount: float, activeSellers: int} */
    public function execute(User $user, CarbonInterface $start, CarbonInterface $end): array
    {
        $metrics = Package::query()
            ->where('company_id', $user->company_id)
            ->whereBetween('received_at', [$start, $end])
            ->toBase()
            ->selectRaw('COUNT(*) as received_packages')
            ->selectRaw('SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as pending_packages', [
                PackageStatus::Received->value,
                PackageStatus::ReadyForPickup->value,
            ])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as delivered_packages', [PackageStatus::Delivered->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled_packages', [PackageStatus::Cancelled->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN status != ? THEN storage_price ELSE 0 END), 0) as storage_amount', [PackageStatus::Cancelled->value])
            ->selectRaw('COUNT(DISTINCT seller_id) as active_sellers')
            ->first();

        return [
            'received' => (int) $metrics->received_packages,
            'pending' => (int) $metrics->pending_packages,
            'delivered' => (int) $metrics->delivered_packages,
            'cancelled' => (int) $metrics->cancelled_packages,
            'storageAmount' => (float) $metrics->storage_amount,
            'activeSellers' => (int) $metrics->active_sellers,
        ];
    }
}
