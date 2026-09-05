<?php

namespace App\Http\Controllers;

use App\Actions\Packages\CreatePackageAction;
use App\Actions\Packages\GeneratePickupQrCodeAction;
use App\Actions\Packages\GeneratePickupTokenAction;
use App\Enums\PackageStatus;
use App\Http\Requests\Packages\StorePackageRequest;
use App\Models\Package;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PackageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Package::class);

        /** @var User $user */
        $user = $request->user();
        $search = $request->string('search')->trim()->toString();
        $status = PackageStatus::tryFrom($request->string('status')->toString());
        $packages = Package::query()
            ->forCompany($user->company)
            ->search($search)
            ->withStatus($status)
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('packages.index', [
            'packages' => $packages,
            'search' => $search,
            'selectedStatus' => $status,
            'statuses' => PackageStatus::cases(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        Gate::authorize('create', Package::class);

        return view('packages.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(
        StorePackageRequest $request,
        CreatePackageAction $createPackage,
        GeneratePickupTokenAction $generatePickupToken,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $package = $createPackage->execute(
            $user,
            $request->safe()->only([
                'sender_name',
                'sender_phone',
                'recipient_name',
                'recipient_phone',
                'description',
                'notes',
            ]),
        );
        $pickupTokenResult = $generatePickupToken->execute($user, $package);

        return redirect()
            ->route('packages.success', $pickupTokenResult['package'])
            ->with('pickup_token', Crypt::encryptString($pickupTokenResult['rawToken']));
    }

    public function success(
        Request $request,
        Package $package,
        GeneratePickupQrCodeAction $generatePickupQrCode,
    ): View {
        Gate::authorize('view', $package);

        $rawToken = $this->pickupTokenFromSession($request);
        $pickupQrDataUri = is_string($rawToken)
            ? $generatePickupQrCode->execute(route('pickup.show', ['token' => $rawToken]))
            : null;

        return view('packages.success', [
            'package' => $package,
            'pickupQrDataUri' => $pickupQrDataUri,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Package $package): View
    {
        Gate::authorize('view', $package);

        $package->load(['branch:id,name', 'receivedBy:id,name']);

        return view('packages.show', ['package' => $package]);
    }

    private function pickupTokenFromSession(Request $request): ?string
    {
        $encryptedPickupToken = $request->session()->get('pickup_token');

        if (! is_string($encryptedPickupToken)) {
            return null;
        }

        try {
            return Crypt::decryptString($encryptedPickupToken);
        } catch (DecryptException) {
            return null;
        }
    }
}
