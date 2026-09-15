<?php

namespace App\Http\Controllers;

use App\Actions\Packages\DeliverPackageAction;
use App\Http\Requests\Pickup\DeliverPickupRequest;
use App\Models\Package;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PickupDeliveryController extends Controller
{
    public function fromQr(DeliverPickupRequest $request, DeliverPackageAction $deliverPackage): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $package = $deliverPackage->execute($user, $request->string('token')->toString());

        return redirect()
            ->route('packages.show', $package)
            ->with('status', 'Paquete entregado correctamente.');
    }

    public function manually(
        Request $request,
        Package $package,
        DeliverPackageAction $deliverPackage,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $deliveredPackage = $deliverPackage->executeManually($user, $package);

        return redirect()
            ->route('packages.show', $deliveredPackage)
            ->with('status', 'Paquete entregado correctamente.');
    }
}
