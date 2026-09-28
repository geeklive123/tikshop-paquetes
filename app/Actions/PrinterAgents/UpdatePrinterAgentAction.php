<?php

namespace App\Actions\PrinterAgents;

use App\Models\Branch;
use App\Models\PrinterAgent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdatePrinterAgentAction
{
    /** @param array{name: string, branch_id: int} $data */
    public function execute(User $user, PrinterAgent $agent, array $data): PrinterAgent
    {
        return DB::transaction(function () use ($user, $agent, $data): PrinterAgent {
            $branch = Branch::query()
                ->whereKey($data['branch_id'])
                ->where('company_id', $user->company_id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedAgent = PrinterAgent::query()
                ->whereKey($agent->getKey())
                ->where('company_id', $user->company_id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedAgent->update([
                'branch_id' => $branch->id,
                'name' => $data['name'],
            ]);

            return $lockedAgent;
        });
    }
}
