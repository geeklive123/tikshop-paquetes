<?php

namespace App\Actions\Printers;

use App\Enums\PrinterConnectionType;
use App\Models\Branch;
use App\Models\Printer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdatePrinterAction
{
    /** @param array{name: string, branch_id: int, connection_type: string, ip_address?: string|null, port?: int|null, paper_width: int, is_default?: bool, active?: bool, notes?: string|null} $data */
    public function execute(User $user, Printer $printer, array $data): Printer
    {
        return DB::transaction(function () use ($user, $printer, $data): Printer {
            $branch = Branch::query()
                ->whereKey($data['branch_id'])
                ->where('company_id', $user->company_id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedPrinter = Printer::query()
                ->whereKey($printer->getKey())
                ->where('company_id', $user->company_id)
                ->lockForUpdate()
                ->firstOrFail();
            $active = $data['active'] ?? true;
            $isDefault = $active && ($data['is_default'] ?? false);

            if ($isDefault) {
                Printer::query()
                    ->where('company_id', $user->company_id)
                    ->whereBelongsTo($branch)
                    ->whereKeyNot($lockedPrinter->getKey())
                    ->update(['is_default' => false]);
            }

            $lockedPrinter->update([
                'branch_id' => $branch->id,
                'name' => $data['name'],
                'connection_type' => $data['connection_type'],
                'ip_address' => $data['connection_type'] === PrinterConnectionType::Lan->value ? ($data['ip_address'] ?? null) : null,
                'port' => $data['connection_type'] === PrinterConnectionType::Lan->value ? ($data['port'] ?? null) : null,
                'paper_width' => $data['paper_width'],
                'is_default' => $isDefault,
                'active' => $active,
                'notes' => $data['notes'] ?? null,
            ]);

            return $lockedPrinter;
        });
    }
}
