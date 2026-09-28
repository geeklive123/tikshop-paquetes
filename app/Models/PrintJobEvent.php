<?php

namespace App\Models;

use App\Enums\PrintJobEventType;
use Database\Factories\PrintJobEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['print_job_id', 'printer_agent_id', 'user_id', 'type', 'message', 'metadata'])]
class PrintJobEvent extends Model
{
    /** @use HasFactory<PrintJobEventFactory> */
    use HasFactory;

    /** @return BelongsTo<PrintJob, $this> */
    public function printJob(): BelongsTo
    {
        return $this->belongsTo(PrintJob::class);
    }

    /** @return BelongsTo<PrinterAgent, $this> */
    public function printerAgent(): BelongsTo
    {
        return $this->belongsTo(PrinterAgent::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => PrintJobEventType::class,
            'metadata' => 'array',
        ];
    }
}
