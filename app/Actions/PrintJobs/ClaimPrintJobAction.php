<?php

namespace App\Actions\PrintJobs;

use App\Enums\PrintJobEventType;
use App\Enums\PrintJobStatus;
use App\Models\PrinterAgent;
use App\Models\PrintJob;
use DomainException;
use Illuminate\Support\Facades\DB;

class ClaimPrintJobAction
{
    public function execute(PrinterAgent $agent, PrintJob $printJob): PrintJob
    {
        return DB::transaction(function () use ($agent, $printJob): PrintJob {
            $lockedJob = PrintJob::query()
                ->whereKey($printJob->getKey())
                ->where('company_id', $agent->company_id)
                ->where('branch_id', $agent->branch_id)
                ->with('printer')
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedJob->status !== PrintJobStatus::Pending) {
                throw new DomainException('El trabajo ya no está pendiente.');
            }

            if (! $lockedJob->printer->active || $lockedJob->printer->branch_id !== $agent->branch_id) {
                throw new DomainException('La impresora asignada no está disponible para este agente.');
            }

            $lockedJob->update([
                'status' => PrintJobStatus::Processing,
                'attempts' => $lockedJob->attempts + 1,
                'printer_agent_id' => $agent->id,
                'claimed_at' => now(),
                'completed_at' => null,
                'failed_at' => null,
                'error_message' => null,
            ]);
            $lockedJob->events()->create([
                'printer_agent_id' => $agent->id,
                'type' => PrintJobEventType::Processing,
                'metadata' => ['attempt' => $lockedJob->attempts],
            ]);

            return $lockedJob->fresh(['printer']);
        });
    }
}
