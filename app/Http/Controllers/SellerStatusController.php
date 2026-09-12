<?php

namespace App\Http\Controllers;

use App\Actions\Sellers\UpdateSellerAction;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SellerStatusController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Seller $seller, UpdateSellerAction $updateSeller): RedirectResponse
    {
        Gate::authorize('toggleStatus', $seller);

        /** @var User $user */
        $user = $request->user();
        $seller = $updateSeller->execute($user, $seller, ['active' => ! $seller->active]);

        return redirect()
            ->route('sellers.show', $seller)
            ->with('status', $seller->active ? 'Vendedor activado correctamente.' : 'Vendedor desactivado correctamente.');
    }
}
