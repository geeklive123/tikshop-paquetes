<?php

namespace App\Actions\Reports;

use App\Actions\Packages\CalculatePackageStorageAmountAction;
use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\Seller;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class GetSellerReportAction
{
    public function __construct(private CalculatePackageStorageAmountAction $calculateStorageAmount) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Seller>
     */
    public function execute(User $user, CarbonInterface $start, CarbonInterface $end, array $filters): LengthAwarePaginator
    {
        $dateConstraint = fn (Builder $query): Builder => $query->whereBetween('received_at', [$start, $end]);
        $sort = (string) ($filters['sort'] ?? 'name');
        $direction = (string) ($filters['direction'] ?? 'asc');

        $sellers = Seller::query()
            ->where('company_id', $user->company_id)
            ->whereHas('packages', $dateConstraint)
            ->withCount([
                'packages as total_packages' => $dateConstraint,
                'packages as pending_packages' => fn (Builder $query): Builder => $dateConstraint($query)->whereIn('status', [PackageStatus::Received, PackageStatus::ReadyForPickup]),
                'packages as delivered_packages' => fn (Builder $query): Builder => $dateConstraint($query)->where('status', PackageStatus::Delivered),
                'packages as cancelled_packages' => fn (Builder $query): Builder => $dateConstraint($query)->where('status', PackageStatus::Cancelled),
            ])
            ->get();
        $amountsBySeller = Package::query()
            ->where('company_id', $user->company_id)
            ->whereBetween('received_at', [$start, $end])
            ->where('status', '!=', PackageStatus::Cancelled)
            ->whereNotNull('seller_id')
            ->get()
            ->groupBy('seller_id')
            ->map(function ($packages): array {
                $amounts = $packages->map(fn (Package $package): array => $this->calculateStorageAmount->execute($package));

                return [
                    'base' => $amounts->sum(fn (array $amount): float => (float) $amount['baseAmount']),
                    'surcharge' => $amounts->sum(fn (array $amount): float => (float) $amount['surchargeAmount']),
                    'total' => $amounts->sum(fn (array $amount): float => (float) $amount['totalAmount']),
                ];
            });

        $sellers->each(function (Seller $seller) use ($amountsBySeller): void {
            $amounts = $amountsBySeller->get($seller->id, ['base' => 0.0, 'surcharge' => 0.0, 'total' => 0.0]);
            $seller->setAttribute('storage_base_amount', $amounts['base']);
            $seller->setAttribute('storage_surcharge_amount', $amounts['surcharge']);
            $seller->setAttribute('storage_amount', $amounts['total']);
        });

        $sortAttribute = match ($sort) {
            'packages' => 'total_packages',
            'amount' => 'storage_amount',
            default => 'name',
        };
        $sellers = $direction === 'desc'
            ? $sellers->sortByDesc($sortAttribute)->values()
            : $sellers->sortBy($sortAttribute)->values();
        $perPage = 15;
        $page = Paginator::resolveCurrentPage();

        return (new LengthAwarePaginator(
            $sellers->forPage($page, $perPage)->values(),
            $sellers->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()],
        ))->withQueryString();
    }
}
