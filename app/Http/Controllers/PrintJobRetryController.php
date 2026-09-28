<?php

namespace App\Http\Controllers;

use App\Actions\PrintJobs\RetryPrintJobAction;
use App\Models\PrintJob;
use App\Models\User;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PrintJobRetryController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, PrintJob $printJob, RetryPrintJobAction $retry): RedirectResponse
    {
        Gate::authorize('update', $printJob);

        try {
            /** @var User $user */
            $user = $request->user();
            $retry->execute($user, $printJob);
        } catch (DomainException $exception) {
            return back()->withErrors(['print_job' => $exception->getMessage()]);
        }

        return back()->with('status', 'Trabajo preparado para reintento.');
    }
}
