<?php

namespace App\Actions\PrinterAgents;

use App\Models\PrinterAgent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegeneratePrinterAgentTokenAction
{
    /** @return array{agent: PrinterAgent, plainToken: string} */
    public function execute(User $user, PrinterAgent $agent): array
    {
        return DB::transaction(function () use ($user, $agent): array {
            $lockedAgent = PrinterAgent::query()
                ->whereKey($agent->getKey())
                ->where('company_id', $user->company_id)
                ->lockForUpdate()
                ->firstOrFail();
            $plainToken = Str::random(64);
            $lockedAgent->update(['token_hash' => hash('sha256', $plainToken)]);

            return ['agent' => $lockedAgent, 'plainToken' => $plainToken];
        });
    }
}
