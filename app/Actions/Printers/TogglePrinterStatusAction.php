<?php

namespace App\Actions\Printers;

use App\Models\Printer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TogglePrinterStatusAction
{
    public function execute(User $user, Printer $printer): Printer
    {
        return DB::transaction(function () use ($user, $printer): Printer {
            $lockedPrinter = Printer::query()
                ->whereKey($printer->getKey())
                ->where('company_id', $user->company_id)
                ->lockForUpdate()
                ->firstOrFail();
            $active = ! $lockedPrinter->active;

            $lockedPrinter->update([
                'active' => $active,
                'is_default' => $active ? $lockedPrinter->is_default : false,
            ]);

            return $lockedPrinter;
        });
    }
}
