<?php

namespace App\Http\Controllers;

use App\Actions\PrinterAgents\RegeneratePrinterAgentTokenAction;
use App\Models\PrinterAgent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PrinterAgentTokenController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        Request $request,
        PrinterAgent $printerAgent,
        RegeneratePrinterAgentTokenAction $regenerate,
    ): RedirectResponse {
        Gate::authorize('update', $printerAgent);
        /** @var User $user */
        $user = $request->user();
        $result = $regenerate->execute($user, $printerAgent);

        return back()->with('status', 'Token regenerado. El anterior dejó de funcionar.')
            ->with('printer_agent_token', $result['plainToken']);
    }
}
