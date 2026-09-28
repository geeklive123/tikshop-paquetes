<?php

namespace App\Models;

use App\Enums\PrintJobStatus;
use App\Enums\PrintJobType;
use Database\Factories\PrintJobFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'branch_id',
    'printer_id',
    'package_id',
    'printer_agent_id',
    'type',
    'status',
    'attempts',
    'payload',
    'error_message',
    'requested_by',
    'claimed_at',
    'completed_at',
    'failed_at',
])]
class PrintJob extends Model
{
    /** @use HasFactory<PrintJobFactory> */
    use HasFactory, HasUlids;

    /** @return array<int, string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return BelongsTo<Printer, $this> */
    public function printer(): BelongsTo
    {
        return $this->belongsTo(Printer::class);
    }

    /** @return BelongsTo<Package, $this> */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /** @return BelongsTo<PrinterAgent, $this> */
    public function printerAgent(): BelongsTo
    {
        return $this->belongsTo(PrinterAgent::class);
    }

    /** @return BelongsTo<User, $this> */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @return HasMany<PrintJobEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(PrintJobEvent::class);
    }

    /**
     * @param  Builder<PrintJob>  $query
     * @return Builder<PrintJob>
     */
    public function scopeForCompany(Builder $query, Company $company): Builder
    {
        return $query->whereBelongsTo($company);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => PrintJobType::class,
            'status' => PrintJobStatus::class,
            'attempts' => 'integer',
            'payload' => 'array',
            'claimed_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }
}
