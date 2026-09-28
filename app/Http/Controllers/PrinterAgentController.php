<?php

namespace App\Http\Controllers;

use App\Actions\PrinterAgents\CreatePrinterAgentAction;
use App\Actions\PrinterAgents\UpdatePrinterAgentAction;
use App\Http\Requests\PrinterAgents\StorePrinterAgentRequest;
use App\Http\Requests\PrinterAgents\UpdatePrinterAgentRequest;
use App\Models\Branch;
use App\Models\PrinterAgent;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PrinterAgentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', PrinterAgent::class);

        return view('printer-agents.create', ['branches' => $this->branchesFor($request)]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePrinterAgentRequest $request, CreatePrinterAgentAction $create): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $result = $create->execute($user, $request->validated());

        return redirect()->route('printer-agents.edit', $result['agent'])
            ->with('status', 'Agente local creado.')
            ->with('printer_agent_token', $result['plainToken']);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, PrinterAgent $printerAgent): View
    {
        Gate::authorize('update', $printerAgent);

        return view('printer-agents.edit', [
            'printerAgent' => $printerAgent,
            'branches' => $this->branchesFor($request),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdatePrinterAgentRequest $request,
        PrinterAgent $printerAgent,
        UpdatePrinterAgentAction $update,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $update->execute($user, $printerAgent, $request->validated());

        return back()->with('status', 'Agente local actualizado.');
    }

    /** @return Collection<int, Branch> */
    private function branchesFor(Request $request): Collection
    {
        /** @var User $user */
        $user = $request->user();

        return Branch::query()
            ->where('company_id', $user->company_id)
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
