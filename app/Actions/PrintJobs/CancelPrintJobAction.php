<?php

namespace App\Actions\PrintJobs;

use App\Enums\PrintJobEventType;
use App\Enums\PrintJobStatus;
use App\Models\PrintJob;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class CancelPrintJobAction
{
    public function execute(User $user, PrintJob $printJob): PrintJob
    {
        return DB::transaction(function () use ($user, $printJob): PrintJob {
            $lockedJob = PrintJob::query()
                ->whereKey($printJob->getKey())
                ->where('company_id', $user->company_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedJob->status !== PrintJobStatus::Pending) {
                throw new DomainException('Solo se pueden cancelar trabajos pendientes.');
            }

            $lockedJob->update(['status' => PrintJobStatus::Cancelled]);
            $lockedJob->events()->create([
                'user_id' => $user->id,
                'type' => PrintJobEventType::Cancelled,
            ]);

            return $lockedJob;
        });
    }
}
