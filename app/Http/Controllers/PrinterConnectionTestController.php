<?php

namespace App\Http\Controllers;

use App\Actions\Printers\TestPrinterConnectionAction;
use App\Models\Printer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class PrinterConnectionTestController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Printer $printer, TestPrinterConnectionAction $testConnection): RedirectResponse
    {
        Gate::authorize('update', $printer);

        return back()->with('status', $testConnection->execute($printer));
    }
}
