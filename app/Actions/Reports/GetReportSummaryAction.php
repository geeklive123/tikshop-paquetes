<?php

namespace App\Actions\Reports;

use App\Actions\Packages\CalculatePackageStorageAmountAction;
use App\Enums\PackagePaymentStatus;
use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\User;
use Carbon\CarbonInterface;

class GetReportSummaryAction
{
    public function __construct(private CalculatePackageStorageAmountAction $calculateStorageAmount) {}

    /** @return array{received: int, delivered: int, cancelled: int, paid: int, paidAmount: float, pending: int, pendingAmount: float, legacy: int, activeSellers: int} */
    public function execute(User $user, CarbonInterface $start, CarbonInterface $end): array
    {
        $companyPackages = Package::query()
            ->where('company_id', $user->company_id)
            ->whereBetween('received_at', [$start, $end]);
        $paidPackages = Package::query()
            ->where('company_id', $user->company_id)
            ->where('payment_status', PackagePaymentStatus::Paid)
            ->whereBetween('paid_at', [$start, $end]);
        $pendingPackages = Package::query()
            ->where('company_id', $user->company_id)
            ->where('payment_status', PackagePaymentStatus::Pending)
            ->whereIn('status', [PackageStatus::Received, PackageStatus::ReadyForPickup])
            ->get();

        return [
            'received' => (clone $companyPackages)->count(),
            'delivered' => Package::query()->where('company_id', $user->company_id)->whereBetween('delivered_at', [$start, $end])->count(),
            'cancelled' => Package::query()->where('company_id', $user->company_id)->whereBetween('cancelled_at', [$start, $end])->count(),
            'paid' => (clone $paidPackages)->count(),
            'paidAmount' => (float) (clone $paidPackages)->sum('paid_amount'),
            'pending' => $pendingPackages->count(),
            'pendingAmount' => $pendingPackages->sum(fn (Package $package): float => (float) $this->calculateStorageAmount->execute($package)['totalAmount']),
            'legacy' => Package::query()->where('company_id', $user->company_id)->where('payment_status', PackagePaymentStatus::Legacy)->count(),
            'activeSellers' => (clone $companyPackages)->whereNotNull('seller_id')->distinct()->count('seller_id'),
        ];
    }
}
