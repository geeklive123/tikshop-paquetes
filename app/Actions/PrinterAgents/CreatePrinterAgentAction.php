<?php

namespace App\Actions\PrinterAgents;

use App\Models\Branch;
use App\Models\PrinterAgent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreatePrinterAgentAction
{
    /**
     * @param  array{name: string, branch_id: int, active?: bool}  $data
     * @return array{agent: PrinterAgent, plainToken: string}
     */
    public function execute(User $user, array $data): array
    {
        return DB::transaction(function () use ($user, $data): array {
            $branch = Branch::query()
                ->whereKey($data['branch_id'])
                ->where('company_id', $user->company_id)
                ->lockForUpdate()
                ->firstOrFail();
            $plainToken = Str::random(64);
            $agent = $user->company->printerAgents()->create([
                'branch_id' => $branch->id,
                'name' => $data['name'],
                'token_hash' => hash('sha256', $plainToken),
                'active' => $data['active'] ?? true,
            ]);

            return ['agent' => $agent, 'plainToken' => $plainToken];
        });
    }
}
