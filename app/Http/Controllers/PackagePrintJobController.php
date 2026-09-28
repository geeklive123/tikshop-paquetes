<?php

namespace App\Http\Controllers;

use App\Actions\PrintJobs\CreatePackageTicketPrintJobAction;
use App\Models\Package;
use App\Models\PrintJob;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PackagePrintJobController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        Request $request,
        Package $package,
        CreatePackageTicketPrintJobAction $createPrintJob,
    ): RedirectResponse {
        Gate::authorize('view', $package);
        Gate::authorize('create', PrintJob::class);

        /** @var User $user */
        $user = $request->user();
        $createPrintJob->execute($user, $package);

        return redirect()->route('packages.show', $package)->with('status', 'Trabajo de impresión enviado');
    }
}
