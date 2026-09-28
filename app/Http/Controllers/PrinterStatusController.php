<?php

namespace App\Http\Controllers;

use App\Actions\Printers\TogglePrinterStatusAction;
use App\Models\Printer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class PrinterStatusController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Printer $printer, TogglePrinterStatusAction $toggleStatus): RedirectResponse
    {
        Gate::authorize('update', $printer);

        /** @var User $user */
        $user = auth()->user();
        $updatedPrinter = $toggleStatus->execute($user, $printer);

        return redirect()
            ->route('printers.index')
            ->with('status', $updatedPrinter->active ? 'Impresora activada correctamente.' : 'Impresora desactivada correctamente.');
    }
}
