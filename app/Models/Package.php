<?php

namespace App\Models;

use App\Enums\PackageStatus;
use Database\Factories\PackageFactory;
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
    'seller_id',
    'package_category_id',
    'tracking_code',
    'storage_code',
    'storage_price',
    'sender_name',
    'sender_phone',
    'recipient_name',
    'recipient_phone',
    'description',
    'notes',
    'status',
    'received_at',
    'ready_at',
    'delivered_at',
    'cancelled_at',
    'received_by',
    'delivered_by',
])]
class Package extends Model
{
    /** @use HasFactory<PackageFactory> */
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

    /** @return BelongsTo<Seller, $this> */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    /** @return BelongsTo<PackageCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(PackageCategory::class, 'package_category_id');
    }

    /** @return BelongsTo<User, $this> */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /** @return BelongsTo<User, $this> */
    public function deliveredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    /** @return HasMany<PackagePickupToken, $this> */
    public function pickupTokens(): HasMany
    {
        return $this->hasMany(PackagePickupToken::class);
    }

    /** @return HasMany<PackageEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(PackageEvent::class);
    }

    /**
     * @param  Builder<Package>  $query
     * @return Builder<Package>
     */
    public function scopeForCompany(Builder $query, Company $company): Builder
    {
        return $query->whereBelongsTo($company);
    }

    /**
     * @param  Builder<Package>  $query
     * @return Builder<Package>
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $query) use ($search): void {
            $query->where('tracking_code', 'like', "%{$search}%")
                ->orWhere('sender_name', 'like', "%{$search}%")
                ->orWhere('sender_phone', 'like', "%{$search}%")
                ->orWhere('recipient_name', 'like', "%{$search}%")
                ->orWhere('recipient_phone', 'like', "%{$search}%");
        });
    }

    /**
     * @param  Builder<Package>  $query
     * @return Builder<Package>
     */
    public function scopeWithStatus(Builder $query, ?PackageStatus $status): Builder
    {
        return $query->when($status, fn (Builder $query): Builder => $query->where('status', $status));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => PackageStatus::class,
            'storage_price' => 'decimal:2',
            'received_at' => 'datetime',
            'ready_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }
}
