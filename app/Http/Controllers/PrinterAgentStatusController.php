<?php

namespace App\Http\Controllers;

use App\Actions\PrinterAgents\TogglePrinterAgentStatusAction;
use App\Models\PrinterAgent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PrinterAgentStatusController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        Request $request,
        PrinterAgent $printerAgent,
        TogglePrinterAgentStatusAction $toggle,
    ): RedirectResponse {
        Gate::authorize('update', $printerAgent);
        /** @var User $user */
        $user = $request->user();
        $updated = $toggle->execute($user, $printerAgent);

        return back()->with('status', $updated->active ? 'Agente activado.' : 'Agente desactivado.');
    }
}
