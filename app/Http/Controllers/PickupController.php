<?php

namespace App\Http\Controllers;

use App\Actions\Packages\CalculatePackageStorageAmountAction;
use App\Actions\Packages\ResolvePickupTokenAction;
use App\Http\Requests\Pickup\ManualPackageSearchRequest;
use App\Http\Requests\Pickup\ResolvePickupRequest;
use App\Models\Package;
use App\Models\PackagePickupToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PickupController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Package::class);

        return view('pickup.scanner');
    }

    public function show(
        Request $request,
        string $token,
        ResolvePickupTokenAction $resolvePickupToken,
        CalculatePackageStorageAmountAction $calculateStorageAmount,
    ): View|Response {
        /** @var User $user */
        $user = $request->user();

        return $this->resolveResponse($user, $token, $resolvePickupToken, $calculateStorageAmount);
    }

    public function resolve(
        ResolvePickupRequest $request,
        ResolvePickupTokenAction $resolvePickupToken,
        CalculatePackageStorageAmountAction $calculateStorageAmount,
    ): View|Response {
        /** @var User $user */
        $user = $request->user();

        return $this->resolveResponse(
            $user,
            $request->string('code')->toString(),
            $resolvePickupToken,
            $calculateStorageAmount,
        );
    }

    public function manual(ManualPackageSearchRequest $request, CalculatePackageStorageAmountAction $calculateStorageAmount): View
    {
        /** @var User $user */
        $user = $request->user();
        $package = Package::query()
            ->forCompany($user->company)
            ->with(['category:id,name', 'paidBy:id,name'])
            ->where('tracking_code', $request->string('tracking_code')->toString())
            ->first();

        return view('pickup.manual', [
            'package' => $package,
            'storageAmount' => $package === null ? null : $calculateStorageAmount->execute($package),
        ]);
    }

    private function resolveResponse(
        User $user,
        string $input,
        ResolvePickupTokenAction $resolvePickupToken,
        CalculatePackageStorageAmountAction $calculateStorageAmount,
    ): View|Response {
        try {
            $pickupToken = $resolvePickupToken->execute($user, $input);
        } catch (ValidationException $exception) {
            return response()->view('pickup.invalid', [
                'message' => $exception->validator->errors()->first('token'),
            ], 422);
        }

        return $this->confirmationView(
            $pickupToken,
            ResolvePickupTokenAction::rawTokenFromInput($input),
            $calculateStorageAmount,
        );
    }

    private function confirmationView(
        PackagePickupToken $pickupToken,
        string $rawToken,
        CalculatePackageStorageAmountAction $calculateStorageAmount,
    ): View {
        $pickupToken->package->loadMissing(['category:id,name', 'paidBy:id,name']);

        return view('pickup.confirm', [
            'package' => $pickupToken->package,
            'rawToken' => $rawToken,
            'storageAmount' => $calculateStorageAmount->execute($pickupToken->package),
        ]);
    }
}
