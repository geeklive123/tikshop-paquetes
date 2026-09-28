<?php

namespace App\Http\Controllers;

use App\Actions\PrintJobs\CancelPrintJobAction;
use App\Models\PrintJob;
use App\Models\User;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PrintJobCancellationController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, PrintJob $printJob, CancelPrintJobAction $cancel): RedirectResponse
    {
        Gate::authorize('update', $printJob);

        try {
            /** @var User $user */
            $user = $request->user();
            $cancel->execute($user, $printJob);
        } catch (DomainException $exception) {
            return back()->withErrors(['print_job' => $exception->getMessage()]);
        }

        return back()->with('status', 'Trabajo pendiente cancelado.');
    }
}
