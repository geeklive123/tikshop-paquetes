<?php

namespace App\Http\Controllers;

use App\Actions\Packages\CreatePackageAction;
use App\Actions\Packages\GeneratePickupQrCodeAction;
use App\Actions\Packages\GeneratePickupTokenAction;
use App\Enums\PackageStatus;
use App\Http\Requests\Packages\StorePackageRequest;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\Seller;
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
            ->with('category:id,name')
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
    public function create(Request $request): View
    {
        Gate::authorize('create', Package::class);

        /** @var User $user */
        $user = $request->user();
        $categories = PackageCategory::query()
            ->whereBelongsTo($user->company)
            ->where('active', true)
            ->orderBy('code_start')
            ->orderBy('name')
            ->get();
        $sellers = Seller::query()
            ->whereBelongsTo($user->company)
            ->where('active', true)
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'ulid', 'name', 'business_name', 'phone']);
        $preselectedSellerId = $sellers
            ->firstWhere('ulid', $request->string('seller')->toString())
            ?->id;

        return view('packages.create', [
            'categories' => $categories,
            'sellers' => $sellers,
            'preselectedSellerId' => $preselectedSellerId,
        ]);
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
                'seller_id',
                'package_category_id',
                'storage_code',
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

        $package->load(['branch:id,name', 'category:id,name']);

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

        $package->load(['branch:id,name', 'category:id,name', 'receivedBy:id,name', 'seller:id,ulid,name,business_name']);

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
