<?php

namespace App\Http\Controllers;

use App\Actions\SellerCommissions\CalculateSellerCommissionAction;
use App\Http\Requests\SellerCommissions\CalculateSellerCommissionRequest;
use App\Models\Seller;
use App\Models\SellerCommissionSettlement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SellerCommissionController extends Controller
{
    public function index(Request $request, Seller $seller): View
    {
        Gate::authorize('viewCommissions', $seller);

        /** @var User $user */
        $user = $request->user();
        $now = now((string) config('app.timezone'));

        return view('sellers.commissions.index', [
            'seller' => $seller,
            'settlements' => $this->settlements($user, $seller),
            'dateFrom' => $now->copy()->startOfMonth()->toDateString(),
            'dateTo' => $now->toDateString(),
            'calculation' => null,
        ]);
    }

    public function calculate(
        CalculateSellerCommissionRequest $request,
        Seller $seller,
        CalculateSellerCommissionAction $calculateCommission,
    ): View {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        return view('sellers.commissions.index', [
            'seller' => $seller,
            'settlements' => $this->settlements($user, $seller),
            'dateFrom' => $data['date_from'],
            'dateTo' => $data['date_to'],
            'calculation' => $calculateCommission->execute($user, $seller, $data['date_from'], $data['date_to']),
        ]);
    }

    /** @return LengthAwarePaginator<int, SellerCommissionSettlement> */
    private function settlements(User $user, Seller $seller): LengthAwarePaginator
    {
        return SellerCommissionSettlement::query()
            ->where('company_id', $user->company_id)
            ->where('seller_id', $seller->id)
            ->with('paidBy:id,name')
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->paginate(10, pageName: 'settlements_page')
            ->withQueryString();
    }
}
