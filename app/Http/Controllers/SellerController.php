<?php

namespace App\Http\Controllers;

use App\Actions\Sellers\CreateSellerAction;
use App\Actions\Sellers\UpdateSellerAction;
use App\Enums\PackageStatus;
use App\Enums\SellerDocumentType;
use App\Http\Requests\Sellers\StoreSellerRequest;
use App\Http\Requests\Sellers\UpdateSellerRequest;
use App\Models\Package;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
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
        Gate::authorize('viewAllPackages', $seller);

        /** @var User $user */
        $user = $request->user();
        $request->validate([
            'status' => ['nullable', Rule::enum(PackageStatus::class)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'search' => ['nullable', 'string', 'max:150'],
        ]);
        $selectedStatus = PackageStatus::tryFrom($request->string('status')->toString());
        $dateFrom = $request->string('date_from')->toString();
        $dateTo = $request->string('date_to')->toString();
        $search = $request->string('search')->trim()->toString();
        $metrics = Package::query()
            ->where('company_id', $user->company_id)
            ->where('seller_id', $seller->id)
            ->toBase()
            ->selectRaw('COUNT(*) as total_packages')
            ->selectRaw('SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as pending_packages', [
                PackageStatus::Received->value,
                PackageStatus::ReadyForPickup->value,
            ])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as delivered_packages', [PackageStatus::Delivered->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled_packages', [PackageStatus::Cancelled->value])
            ->selectRaw('COALESCE(SUM(storage_price), 0) as storage_amount')
            ->first();

        $packagesQuery = $seller->packages()
            ->where('company_id', $user->company_id)
            ->with('category:id,name')
            ->orderByDesc('received_at')
            ->orderByDesc('id');

        $packages = $packagesQuery
            ->withStatus($selectedStatus)
            ->when($dateFrom !== '', fn (Builder $query): Builder => $query->whereDate('received_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn (Builder $query): Builder => $query->whereDate('received_at', '<=', $dateTo))
            ->when($search !== '', fn (Builder $query): Builder => $query->where(function (Builder $query) use ($search): void {
                $query->where('tracking_code', 'like', "%{$search}%")
                    ->orWhere('recipient_name', 'like', "%{$search}%");
            }))
            ->paginate(15)
            ->withQueryString();

        return view('sellers.show', [
            'seller' => $seller,
            'metrics' => $metrics,
            'packages' => $packages,
            'statuses' => PackageStatus::cases(),
            'selectedStatus' => $selectedStatus,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'search' => $search,
        ]);
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
