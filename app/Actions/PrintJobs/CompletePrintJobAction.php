<?php

namespace App\Actions\PrintJobs;

use App\Enums\PrintJobEventType;
use App\Enums\PrintJobStatus;
use App\Models\PrinterAgent;
use App\Models\PrintJob;
use DomainException;
use Illuminate\Support\Facades\DB;

class CompletePrintJobAction
{
    public function execute(PrinterAgent $agent, PrintJob $printJob, ?string $resultMessage = null): PrintJob
    {
        return DB::transaction(function () use ($agent, $printJob, $resultMessage): PrintJob {
            $lockedJob = $this->processingJobFor($agent, $printJob);
            $lockedJob->update([
                'status' => PrintJobStatus::Completed,
                'completed_at' => now(),
                'failed_at' => null,
                'error_message' => null,
            ]);
            $lockedJob->events()->create([
                'printer_agent_id' => $agent->id,
                'type' => PrintJobEventType::Completed,
                'message' => $resultMessage,
            ]);

            return $lockedJob;
        });
    }

    private function processingJobFor(PrinterAgent $agent, PrintJob $printJob): PrintJob
    {
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

        return $lockedJob;
    }
}
