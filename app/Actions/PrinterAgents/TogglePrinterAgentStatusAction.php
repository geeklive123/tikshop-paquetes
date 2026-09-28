<?php

namespace App\Actions\PrinterAgents;

use App\Models\PrinterAgent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TogglePrinterAgentStatusAction
{
    public function execute(User $user, PrinterAgent $agent): PrinterAgent
    {
        return DB::transaction(function () use ($user, $agent): PrinterAgent {
            $lockedAgent = PrinterAgent::query()
                ->whereKey($agent->getKey())
                ->where('company_id', $user->company_id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedAgent->update(['active' => ! $lockedAgent->active]);

            return $lockedAgent;
        });
    }
}
