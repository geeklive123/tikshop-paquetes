<?php

namespace App\Actions\Reports;

use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class GetCancellationReportAction
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Package>
     */
    public function execute(User $user, CarbonInterface $start, CarbonInterface $end, array $filters): LengthAwarePaginator
    {
        return Package::query()
            ->where('company_id', $user->company_id)
            ->where('status', PackageStatus::Cancelled)
            ->whereBetween('cancelled_at', [$start, $end])
            ->when(isset($filters['seller_id']), fn (Builder $query): Builder => $query->where('seller_id', (int) $filters['seller_id']))
            ->when(isset($filters['cancelled_by']), fn (Builder $query): Builder => $query->where('cancelled_by', (int) $filters['cancelled_by']))
            ->with(['seller:id,name,business_name', 'cancelledBy:id,name'])
            ->orderByDesc('cancelled_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
    }
}
