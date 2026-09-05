<?php

namespace App\Http\Controllers;

use App\Actions\Packages\DeliverPackageAction;
use App\Http\Requests\Pickup\DeliverPickupRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class PickupDeliveryController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(DeliverPickupRequest $request, DeliverPackageAction $deliverPackage): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $package = $deliverPackage->execute($user, $request->string('token')->toString());

        return redirect()
            ->route('packages.show', $package)
            ->with('status', 'Paquete entregado correctamente.');
    }
}
