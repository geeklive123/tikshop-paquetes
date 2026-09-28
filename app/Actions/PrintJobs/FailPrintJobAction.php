<?php

namespace App\Actions\PrintJobs;

use App\Enums\PrintJobEventType;
use App\Enums\PrintJobStatus;
use App\Models\PrinterAgent;
use App\Models\PrintJob;
use DomainException;
use Illuminate\Support\Facades\DB;

class FailPrintJobAction
{
    public function execute(PrinterAgent $agent, PrintJob $printJob, string $errorMessage): PrintJob
    {
        return DB::transaction(function () use ($agent, $printJob, $errorMessage): PrintJob {
            $lockedJob = PrintJob::query()
                ->whereKey($printJob->getKey())
                ->where('company_id', $agent->company_id)
                ->where('branch_id', $agent->branch_id)
                ->where('printer_agent_id', $agent->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedJob->status !== PrintJobStatus::Processing) {
                throw new DomainException('El trabajo no está en procesamiento.');
            }

            $lockedJob->update([
                'status' => PrintJobStatus::Failed,
                'error_message' => $errorMessage,
                'failed_at' => now(),
                'completed_at' => null,
            ]);
            $lockedJob->events()->create([
                'printer_agent_id' => $agent->id,
                'type' => PrintJobEventType::Failed,
                'message' => $errorMessage,
            ]);

            return $lockedJob;
        });
    }
}
