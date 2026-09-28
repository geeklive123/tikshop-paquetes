<?php

namespace App\Actions\Reports;

use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class GetPackageReportAction
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Package>
     */
    public function execute(User $user, CarbonInterface $start, CarbonInterface $end, array $filters, ?int $sellerId = null): LengthAwarePaginator
    {
        $status = PackageStatus::tryFrom((string) ($filters['status'] ?? ''));
        $trackingCode = trim((string) ($filters['tracking_code'] ?? ''));
        $recipient = trim((string) ($filters['recipient'] ?? ''));

        return Package::query()
            ->where('company_id', $user->company_id)
            ->whereBetween('received_at', [$start, $end])
            ->when($sellerId !== null, fn (Builder $query): Builder => $query->where('seller_id', $sellerId))
            ->when($sellerId === null && isset($filters['seller_id']), fn (Builder $query): Builder => $query->where('seller_id', (int) $filters['seller_id']))
            ->when(isset($filters['category_id']), fn (Builder $query): Builder => $query->where('package_category_id', (int) $filters['category_id']))
            ->when(isset($filters['branch_id']), fn (Builder $query): Builder => $query->where('branch_id', (int) $filters['branch_id']))
            ->withStatus($status)
            ->when($trackingCode !== '', fn (Builder $query): Builder => $query->where('tracking_code', 'like', "%{$trackingCode}%"))
            ->when($recipient !== '', fn (Builder $query): Builder => $query->where('recipient_name', 'like', "%{$recipient}%"))
            ->with(['seller:id,name,business_name', 'category:id,name', 'branch:id,name'])
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
    }
}
