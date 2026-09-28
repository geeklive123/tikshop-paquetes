<?php

namespace App\Http\Controllers;

use App\Actions\Printers\CreatePrinterAction;
use App\Actions\Printers\UpdatePrinterAction;
use App\Enums\PrinterConnectionType;
use App\Http\Requests\Printers\StorePrinterRequest;
use App\Http\Requests\Printers\UpdatePrinterRequest;
use App\Models\Branch;
use App\Models\Printer;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PrinterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Printer::class);

        /** @var User $user */
        $user = $request->user();
        $printers = Printer::query()
            ->forCompany($user->company)
            ->with('branch:id,name')
            ->orderByDesc('active')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15);

        return view('printers.index', ['printers' => $printers]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Printer::class);

        return view('printers.create', [
            'branches' => $this->branchesFor($request),
            'connectionTypes' => PrinterConnectionType::cases(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePrinterRequest $request, CreatePrinterAction $createPrinter): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $printer = $createPrinter->execute($user, $request->validated());

        return redirect()
            ->route('printers.edit', $printer)
            ->with('status', 'Impresora creada correctamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Printer $printer): View
    {
        Gate::authorize('update', $printer);

        return view('printers.edit', [
            'printer' => $printer,
            'branches' => $this->branchesFor($request, $printer),
            'connectionTypes' => PrinterConnectionType::cases(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdatePrinterRequest $request,
        Printer $printer,
        UpdatePrinterAction $updatePrinter,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $updatedPrinter = $updatePrinter->execute($user, $printer, $request->validated());

        return redirect()
            ->route('printers.edit', $updatedPrinter)
            ->with('status', 'Impresora actualizada correctamente.');
    }

    /** @return Collection<int, Branch> */
    private function branchesFor(Request $request, ?Printer $printer = null): Collection
    {
        /** @var User $user */
        $user = $request->user();

        return Branch::query()
            ->whereBelongsTo($user->company)
            ->where(function ($query) use ($printer): void {
                $query->where('active', true)
                    ->when($printer, fn ($query) => $query->orWhereKey($printer->branch_id));
            })
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
