<?php

namespace App\Http\Controllers;

use App\Actions\Packages\CancelPackageAction;
use App\Http\Requests\Packages\CancelPackageRequest;
use App\Models\Package;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class PackageCancellationController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        CancelPackageRequest $request,
        Package $package,
        CancelPackageAction $cancelPackage,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $package = $cancelPackage->execute($user, $package, $request->string('reason')->trim()->toString());

        return redirect()
            ->route('packages.show', $package)
            ->with('status', 'Paquete anulado correctamente.');
    }
}
