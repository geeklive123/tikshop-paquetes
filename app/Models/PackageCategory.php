<?php

namespace App\Models;

use Database\Factories\PackageCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'name', 'code_prefix', 'code_start', 'code_end', 'price', 'color', 'active'])]
class PackageCategory extends Model
{
    /** @use HasFactory<PackageCategoryFactory> */
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

    /** @return HasMany<Package, $this> */
    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    public function codeRange(): string
    {
        return "{$this->code_prefix}{$this->code_start} – {$this->code_prefix}{$this->code_end}";
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'active' => 'boolean',
        ];
    }
}
