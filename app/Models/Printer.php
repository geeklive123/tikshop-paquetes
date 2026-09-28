<?php

namespace App\Models;

use App\Enums\PrinterConnectionType;
use Database\Factories\PrinterFactory;
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
    'name',
    'connection_type',
    'ip_address',
    'port',
    'paper_width',
    'is_default',
    'active',
    'notes',
])]
class Printer extends Model
{
    /** @use HasFactory<PrinterFactory> */
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

    /** @return HasMany<PrintJob, $this> */
    public function printJobs(): HasMany
    {
        return $this->hasMany(PrintJob::class);
    }

    /**
     * @param  Builder<Printer>  $query
     * @return Builder<Printer>
     */
    public function scopeForCompany(Builder $query, Company $company): Builder
    {
        return $query->whereBelongsTo($company);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'connection_type' => PrinterConnectionType::class,
            'paper_width' => 'integer',
            'is_default' => 'boolean',
            'active' => 'boolean',
        ];
    }
}
