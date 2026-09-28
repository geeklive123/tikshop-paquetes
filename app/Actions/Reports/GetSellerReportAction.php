<?php

namespace App\Actions\Reports;

use App\Enums\PackageStatus;
use App\Models\Seller;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class GetSellerReportAction
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Seller>
     */
    public function execute(User $user, CarbonInterface $start, CarbonInterface $end, array $filters): LengthAwarePaginator
    {
        $dateConstraint = fn (Builder $query): Builder => $query->whereBetween('received_at', [$start, $end]);
        $validAmountConstraint = fn (Builder $query): Builder => $query
            ->whereBetween('received_at', [$start, $end])
            ->where('status', '!=', PackageStatus::Cancelled);
        $sortColumns = [
            'name' => 'name',
            'packages' => 'total_packages',
            'amount' => 'storage_amount',
        ];
        $sort = (string) ($filters['sort'] ?? 'name');
        $direction = (string) ($filters['direction'] ?? 'asc');

        return Seller::query()
            ->where('company_id', $user->company_id)
            ->whereHas('packages', $dateConstraint)
            ->withCount([
                'packages as total_packages' => $dateConstraint,
                'packages as pending_packages' => fn (Builder $query): Builder => $dateConstraint($query)->whereIn('status', [PackageStatus::Received, PackageStatus::ReadyForPickup]),
                'packages as delivered_packages' => fn (Builder $query): Builder => $dateConstraint($query)->where('status', PackageStatus::Delivered),
                'packages as cancelled_packages' => fn (Builder $query): Builder => $dateConstraint($query)->where('status', PackageStatus::Cancelled),
            ])
            ->withSum(['packages as storage_amount' => $validAmountConstraint], 'storage_price')
            ->orderBy($sortColumns[$sort], $direction)
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();
    }
}
