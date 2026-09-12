<?php

namespace App\Http\Controllers;

use App\Actions\Sellers\CreateSellerAction;
use App\Actions\Sellers\UpdateSellerAction;
use App\Enums\PackageStatus;
use App\Enums\SellerDocumentType;
use App\Http\Requests\Sellers\StoreSellerRequest;
use App\Http\Requests\Sellers\UpdateSellerRequest;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SellerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Seller::class);

        /** @var User $user */
        $user = $request->user();
        $search = $request->string('search')->trim()->toString();
        $sellers = Seller::query()
            ->whereBelongsTo($user->company)
            ->search($search)
            ->withCount('packages')
            ->orderByDesc('active')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('sellers.index', ['sellers' => $sellers, 'search' => $search]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Seller::class);

        return view('sellers.create', [
            'documentTypes' => SellerDocumentType::cases(),
            'returnTo' => $request->string('return_to')->toString() === 'packages.create'
                ? 'packages.create'
                : null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSellerRequest $request, CreateSellerAction $createSeller): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $seller = $createSeller->execute($user, $request->safe()->only([
            'name',
            'business_name',
            'phone',
            'document_type',
            'document_number',
            'address',
            'notes',
            'active',
        ]));

        if ($request->validated('return_to') === 'packages.create') {
            return redirect()
                ->route('packages.create', ['seller' => $seller->ulid])
                ->with('status', 'Vendedor creado y seleccionado correctamente.');
        }

        return redirect()
            ->route('sellers.show', $seller)
            ->with('status', 'Vendedor creado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Seller $seller): View
    {
        Gate::authorize('view', $seller);

        /** @var User $user */
        $user = $request->user();
        $seller->loadCount([
            'packages',
            'packages as pending_packages_count' => fn (Builder $query): Builder => $query->whereIn('status', [
                PackageStatus::Received,
                PackageStatus::ReadyForPickup,
            ]),
            'packages as delivered_packages_count' => fn (Builder $query): Builder => $query->where('status', PackageStatus::Delivered),
        ]);
        $recentPackages = $seller->packages()
            ->where('company_id', $user->company_id)
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('sellers.show', ['seller' => $seller, 'recentPackages' => $recentPackages]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Seller $seller): View
    {
        Gate::authorize('update', $seller);

        return view('sellers.edit', [
            'seller' => $seller,
            'documentTypes' => SellerDocumentType::cases(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateSellerRequest $request,
        Seller $seller,
        UpdateSellerAction $updateSeller,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $seller = $updateSeller->execute($user, $seller, $request->validated());

        return redirect()
            ->route('sellers.show', $seller)
            ->with('status', 'Vendedor actualizado correctamente.');
    }

    public function destroy(Seller $seller): never
    {
        abort(405);
    }
}
