<?php

namespace App\Actions\Reports;

use App\Models\SellerCommissionSettlement;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class GetPaidCommissionReportAction
{
    /** @return LengthAwarePaginator<int, SellerCommissionSettlement> */
    public function execute(User $user, CarbonInterface $start, CarbonInterface $end): LengthAwarePaginator
    {
        return SellerCommissionSettlement::query()
            ->where('company_id', $user->company_id)
            ->whereBetween('paid_at', [$start, $end])
            ->with(['seller:id,ulid,name,business_name', 'paidBy:id,name'])
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
    }
}
