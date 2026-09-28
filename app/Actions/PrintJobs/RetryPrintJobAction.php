<?php

namespace App\Actions\PrintJobs;

use App\Enums\PrintJobEventType;
use App\Enums\PrintJobStatus;
use App\Models\PrintJob;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class RetryPrintJobAction
{
    public function execute(User $user, PrintJob $printJob): PrintJob
    {
        return DB::transaction(function () use ($user, $printJob): PrintJob {
            $lockedJob = PrintJob::query()
                ->whereKey($printJob->getKey())
                ->where('company_id', $user->company_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedJob->status !== PrintJobStatus::Failed) {
                throw new DomainException('Solo se pueden reintentar trabajos fallidos.');
            }

            $lockedJob->update([
                'status' => PrintJobStatus::Pending,
                'printer_agent_id' => null,
                'claimed_at' => null,
                'completed_at' => null,
                'failed_at' => null,
                'error_message' => null,
            ]);
            $lockedJob->events()->create([
                'user_id' => $user->id,
                'type' => PrintJobEventType::Retried,
                'metadata' => ['next_attempt' => $lockedJob->attempts + 1],
            ]);

            return $lockedJob;
        });
    }
}
