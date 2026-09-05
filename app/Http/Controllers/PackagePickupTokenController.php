<?php

namespace App\Http\Controllers;

use App\Actions\Packages\GeneratePickupTokenAction;
use App\Models\Package;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;

class PackagePickupTokenController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Package $package, GeneratePickupTokenAction $generatePickupToken): RedirectResponse
    {
        Gate::authorize('generatePickupToken', $package);

        /** @var User $user */
        $user = $request->user();
        $result = $generatePickupToken->execute($user, $package);

        return redirect()
            ->route('packages.success', $result['package'])
            ->with('pickup_token', Crypt::encryptString($result['rawToken']))
            ->with('status', 'Código QR generado correctamente.');
    }
}
