<?php

namespace App\Http\Controllers;

use App\Actions\SellerCommissions\CreateSellerCommissionSettlementAction;
use App\Http\Requests\SellerCommissions\StoreSellerCommissionSettlementRequest;
use App\Models\Seller;
use App\Models\SellerCommissionSettlement;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SellerCommissionSettlementController extends Controller
{
    public function store(
        StoreSellerCommissionSettlementRequest $request,
        Seller $seller,
        CreateSellerCommissionSettlementAction $createSettlement,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $settlement = $createSettlement->execute($user, $seller, $request->validated());

        return redirect()
            ->route('sellers.commission-settlements.show', [$seller, $settlement])
            ->with('status', 'Pago de comisión confirmado correctamente.');
    }

    public function show(Seller $seller, SellerCommissionSettlement $sellerCommissionSettlement): View
    {
        Gate::authorize('viewCommissions', $seller);
        Gate::authorize('view', $sellerCommissionSettlement);

        $sellerCommissionSettlement->load([
            'paidBy:id,name',
            'items' => fn (Builder $query): Builder => $query->orderBy('id'),
            'items.package:id,ulid,tracking_code,recipient_name,delivered_at',
        ]);

        return view('sellers.commissions.show', [
            'seller' => $seller,
            'settlement' => $sellerCommissionSettlement,
        ]);
    }
}
