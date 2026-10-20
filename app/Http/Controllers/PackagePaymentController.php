<?php

namespace App\Http\Controllers;

use App\Actions\Packages\MarkPackageAsPaidAction;
use App\Models\Package;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PackagePaymentController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        Request $request,
        Package $package,
        MarkPackageAsPaidAction $markPackageAsPaid,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $package = $markPackageAsPaid->execute($user, $package);

        return redirect()
            ->route('packages.show', $package)
            ->with('status', 'Pago de almacenaje registrado correctamente.');
    }
}
