<?php

namespace App\Actions\Printers;

use App\Enums\PrinterConnectionType;
use App\Models\Branch;
use App\Models\Printer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreatePrinterAction
{
    /** @param array{name: string, branch_id: int, connection_type: string, ip_address?: string|null, port?: int|null, paper_width: int, copies: int, is_default?: bool, active?: bool, notes?: string|null} $data */
    public function execute(User $user, array $data): Printer
    {
        return DB::transaction(function () use ($user, $data): Printer {
            $branch = Branch::query()
                ->whereKey($data['branch_id'])
                ->where('company_id', $user->company_id)
                ->lockForUpdate()
                ->firstOrFail();

            $attributes = $this->normalizedAttributes($data);

            if ($attributes['is_default']) {
                Printer::query()
                    ->where('company_id', $user->company_id)
                    ->whereBelongsTo($branch)
                    ->update(['is_default' => false]);
            }

            return $user->company->printers()->create([
                ...$attributes,
                'branch_id' => $branch->id,
            ]);
        });
    }

    /**
     * @param  array{name: string, branch_id: int, connection_type: string, ip_address?: string|null, port?: int|null, paper_width: int, copies: int, is_default?: bool, active?: bool, notes?: string|null}  $data
     * @return array{name: string, connection_type: string, ip_address: string|null, port: int|null, paper_width: int, copies: int, is_default: bool, active: bool, notes: string|null}
     */
    private function normalizedAttributes(array $data): array
    {
        $active = $data['active'] ?? true;

        return [
            'name' => $data['name'],
            'connection_type' => $data['connection_type'],
            'ip_address' => $data['connection_type'] === PrinterConnectionType::Lan->value ? ($data['ip_address'] ?? null) : null,
            'port' => $data['connection_type'] === PrinterConnectionType::Lan->value ? ($data['port'] ?? null) : null,
            'paper_width' => $data['paper_width'],
            'copies' => $data['copies'],
            'is_default' => $active && ($data['is_default'] ?? false),
            'active' => $active,
            'notes' => $data['notes'] ?? null,
        ];
    }
}
