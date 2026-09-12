<?php

namespace App\Models;

use App\Enums\SellerDocumentType;
use Database\Factories\SellerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'name', 'business_name', 'phone', 'document_type', 'document_number', 'address', 'notes', 'active'])]
class Seller extends Model
{
    /** @use HasFactory<SellerFactory> */
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

    /**
     * @param  Builder<Seller>  $query
     * @return Builder<Seller>
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $query) use ($search): void {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('business_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('document_number', 'like', "%{$search}%");
        });
    }

    public function snapshotName(): string
    {
        return $this->business_name ?: $this->name;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'document_type' => SellerDocumentType::class,
            'active' => 'boolean',
        ];
    }
}
